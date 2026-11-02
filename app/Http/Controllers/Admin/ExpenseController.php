<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkExpenseRequest;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Support\Flash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Expense::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'in:0,1'],
            'created_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'sort' => ['nullable', Rule::in([
                'name',
                'overkm_rate',
                'overtime_rate',
                'overnight_rate',
                'holiday_rate',
                'created_at',
                'updated_at',
            ])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $search = trim($filters['search'] ?? '');
        $isActive = array_key_exists('is_active', $filters) && $filters['is_active'] !== null
            ? (int) $filters['is_active']
            : null;
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['dir'] ?? 'desc';

        $expenses = Expense::query()
            ->select([
                'id',
                'name',
                'overkm_rate',
                'overtime_rate',
                'overnight_rate',
                'holiday_rate',
                'note',
                'is_active',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->with('creator:id,name')
            ->withCount('tripExpenses')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subquery) use ($search): void {
                    if (ctype_digit($search)) {
                        $subquery->orWhere('id', (int) $search);
                    }

                    $subquery
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%");
                });
            })
            ->when($filters['created_by'] ?? null, fn (Builder $query, int $creatorId) => $query->where('created_by', $creatorId))
            ->when(! is_null($isActive), fn (Builder $query) => $query->where('is_active', $isActive))
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $authUser = $request->user();
        $expenses->getCollection()->transform(function (Expense $expense) use ($authUser): Expense {
            $expense->setAttribute('can', [
                'update' => $authUser->can('update', $expense),
                'delete' => $authUser->can('delete', $expense) && $expense->trip_expenses_count === 0,
                'toggleActive' => $authUser->can('toggleActive', $expense),
            ]);

            return $expense;
        });

        return Inertia::render('admin/Expense', [
            'expenses' => $expenses,
            'expensesMeta' => [
                'total' => $expenses->total(),
                'from' => $expenses->firstItem(),
                'to' => $expenses->lastItem(),
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
            ],
            'stats' => [
                'total' => Expense::count(),
                'active' => Expense::where('is_active', true)->count(),
                'inactive' => Expense::where('is_active', false)->count(),
            ],
            'creators' => User::query()
                ->whereIn('id', Expense::query()->whereNotNull('created_by')->distinct()->pluck('created_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'is_active' => is_null($isActive) ? null : (string) $isActive,
                'created_by' => $filters['created_by'] ?? null,
                'sort' => $sort,
                'dir' => $direction,
            ],
            'can' => [
                'create' => $authUser->can('create', Expense::class),
                'toggleActive' => $authUser->can('toggleActiveAny', Expense::class),
                'delete' => $authUser->can('deleteAny', Expense::class),
            ],
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'is_active' => $request->validated('is_active', true),
        ]);

        return back(303)->with('flash', Flash::success('expense.flash.created'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return back(303)->with('flash', Flash::success('expense.flash.updated', [
            'name' => $expense->name,
        ]));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);
        $expense->delete();

        return back(303)->with('flash', Flash::success('expense.flash.deleted', [
            'name' => $expense->name,
        ]));
    }

    public function toggleActive(Expense $expense): RedirectResponse
    {
        Gate::authorize('toggleActive', $expense);
        $expense->update(['is_active' => ! $expense->is_active]);

        return back(303)->with('flash', Flash::success(
            $expense->is_active ? 'expense.flash.activated' : 'expense.flash.deactivated',
            ['name' => $expense->name],
        ));
    }

    public function bulk(BulkExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $authUser = $request->user();
        $targets = Expense::query()
            ->withCount('tripExpenses')
            ->whereIn('id', $data['expense_ids'])
            ->get();
        $affected = 0;
        $skipped = 0;

        DB::transaction(function () use ($authUser, $data, $targets, &$affected, &$skipped): void {
            foreach ($targets as $expense) {
                $ability = $data['action'] === 'delete' ? 'delete' : 'toggleActive';

                if (
                    $authUser->cannot($ability, $expense)
                    || ($data['action'] === 'delete' && $expense->trip_expenses_count > 0)
                ) {
                    $skipped++;

                    continue;
                }

                match ($data['action']) {
                    'activate' => $expense->update(['is_active' => true]),
                    'deactivate' => $expense->update(['is_active' => false]),
                    'delete' => $expense->delete(),
                };
                $affected++;
            }
        });

        if ($affected === 0) {
            return back(303)->with('flash', Flash::warning('expense.flash.bulkNoChanges'));
        }

        $messages = Flash::success('expense.flash.bulkCompleted', ['count' => $affected]);

        if ($skipped > 0) {
            $messages = Flash::many($messages, Flash::warning('expense.flash.bulkSkipped', ['count' => $skipped]));
        }

        return back(303)->with('flash', $messages);
    }
}
