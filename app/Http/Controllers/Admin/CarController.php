<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkCarRequest;
use App\Http\Requests\Admin\StoreCarRequest;
use App\Http\Requests\Admin\UpdateCarRequest;
use App\Models\Car;
use App\Support\Flash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CarController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Car::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:0,1'],
            'sort' => ['nullable', Rule::in(['name', 'brand', 'model', 'year', 'license_plate', 'owner', 'created_at', 'updated_at'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $search = trim($filters['search'] ?? '');
        $isActive = array_key_exists('is_active', $filters) && $filters['is_active'] !== null
            ? (int) $filters['is_active']
            : null;
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['dir'] ?? 'desc';

        $cars = Car::query()
            ->select([
                'id',
                'name',
                'brand',
                'model',
                'year',
                'license_plate',
                'owner',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subquery) use ($search): void {
                    if (ctype_digit($search)) {
                        $subquery->orWhere('id', (int) $search);
                    }

                    $subquery
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('license_plate', 'like', "%{$search}%")
                        ->orWhere('owner', 'like', "%{$search}%");
                });
            })
            ->when($filters['brand'] ?? null, fn (Builder $query, string $brand) => $query->where('brand', $brand))
            ->when(! is_null($isActive), fn (Builder $query) => $query->where('is_active', $isActive))
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $authUser = $request->user();
        $cars->getCollection()->transform(function (Car $car) use ($authUser): Car {
            $car->setAttribute('can', [
                'update' => $authUser->can('update', $car),
                'delete' => $authUser->can('delete', $car),
                'toggleActive' => $authUser->can('toggleActive', $car),
            ]);

            return $car;
        });

        return Inertia::render('admin/Car', [
            'cars' => $cars,
            'carsMeta' => [
                'total' => $cars->total(),
                'from' => $cars->firstItem(),
                'to' => $cars->lastItem(),
                'current_page' => $cars->currentPage(),
                'last_page' => $cars->lastPage(),
                'per_page' => $cars->perPage(),
            ],
            'stats' => [
                'total' => Car::count(),
                'active' => Car::where('is_active', true)->count(),
                'inactive' => Car::where('is_active', false)->count(),
            ],
            'brands' => Car::query()
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand'),
            'filters' => [
                'search' => $search ?: null,
                'brand' => $filters['brand'] ?? null,
                'is_active' => is_null($isActive) ? null : (string) $isActive,
                'sort' => $sort,
                'dir' => $direction,
            ],
            'can' => [
                'create' => $authUser->can('create', Car::class),
                'toggleActive' => $authUser->can('toggleActiveAny', Car::class),
                'delete' => $authUser->can('deleteAny', Car::class),
            ],
        ]);
    }

    public function store(StoreCarRequest $request): RedirectResponse
    {
        Car::create([
            ...$request->validated(),
            'is_active' => $request->validated('is_active', true),
        ]);

        return back(303)->with('flash', Flash::success('car.flash.created'));
    }

    public function update(UpdateCarRequest $request, Car $car): RedirectResponse
    {
        $car->update($request->validated());

        return back(303)->with('flash', Flash::success('car.flash.updated', [
            'name' => $car->name,
        ]));
    }

    public function destroy(Car $car): RedirectResponse
    {
        Gate::authorize('delete', $car);

        $car->delete();

        return back(303)->with('flash', Flash::success('car.flash.deleted', [
            'name' => $car->name,
        ]));
    }

    public function toggleActive(Car $car): RedirectResponse
    {
        Gate::authorize('toggleActive', $car);

        $car->update(['is_active' => ! $car->is_active]);

        return back(303)->with('flash', Flash::success(
            $car->is_active ? 'car.flash.activated' : 'car.flash.deactivated',
            ['name' => $car->name],
        ));
    }

    public function bulk(BulkCarRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $authUser = $request->user();
        $targets = Car::query()
            ->withCount('trips')
            ->whereIn('id', $data['car_ids'])
            ->get();
        $affected = 0;
        $skipped = 0;

        DB::transaction(function () use ($authUser, $data, $targets, &$affected, &$skipped): void {
            foreach ($targets as $car) {
                $ability = $data['action'] === 'delete' ? 'delete' : 'toggleActive';

                if ($authUser->cannot($ability, $car) || ($data['action'] === 'delete' && $car->trips_count > 0)) {
                    $skipped++;

                    continue;
                }

                match ($data['action']) {
                    'activate' => $car->update(['is_active' => true]),
                    'deactivate' => $car->update(['is_active' => false]),
                    'delete' => $car->delete(),
                };
                $affected++;
            }
        });

        if ($affected === 0) {
            return back(303)->with('flash', Flash::warning('car.flash.bulkNoChanges'));
        }

        $messages = Flash::success('car.flash.bulkCompleted', ['count' => $affected]);

        if ($skipped > 0) {
            $messages = Flash::many($messages, Flash::warning('car.flash.bulkSkipped', ['count' => $skipped]));
        }

        return back(303)->with('flash', $messages);
    }
}
