<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserAccountNotification;
use App\Support\Flash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

    /** Role chỉ admin mới được gán */
    private const PROTECTED_ROLES = ['admin'];

    public function index(Request $request)
    {
        $authUser = $request->user();
        Gate::authorize('viewAny', User::class);

        /* ---------- 1. Validate & chuẩn hoá filter ---------- */
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => [
                'nullable',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'is_active' => ['nullable', 'in:0,1'],
            'verified' => ['nullable', 'in:0,1'],
            'created_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'sort' => ['nullable', 'in:name,email,created_at,updated_at'],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);

        $search = trim($filters['search'] ?? '');
        $role = $filters['role'] ?? null;
        $createdBy = $filters['created_by'] ?? null;
        $sort = $filters['sort'] ?? 'created_at';
        $dir = $filters['dir'] ?? 'desc';

        // array_key_exists: KHÔNG dùng ?: để tránh nuốt mất giá trị '0'
        $isActive = array_key_exists('is_active', $filters) && $filters['is_active'] !== null
            ? (int) $filters['is_active']
            : null;

        $verified = array_key_exists('verified', $filters) && $filters['verified'] !== null
            ? (int) $filters['verified']
            : null;

        /* ---------- 2. Query ---------- */
        $query = User::query()
            ->with([
                'roles:id,name',
                'creator:id,name',
            ])
            ->with('permissions:id,name')
            // ----- Tìm kiếm: ID / tên / email -----
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    if (ctype_digit($search)) {
                        $sub->orWhere('id', (int) $search);
                    }
                    $sub->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            // ----- Lọc theo vai trò -----
            ->when($role, fn ($q, $value) => $q->whereHas('roles', fn ($r) => $r->where('name', $value)))
            // ----- Trạng thái kích hoạt -----
            ->when(! is_null($isActive), fn ($q) => $q->where('is_active', $isActive))
            // ----- Xác minh email -----
            ->when(! is_null($verified), function ($q) use ($verified) {
                $verified === 1
                    ? $q->whereNotNull('email_verified_at')
                    : $q->whereNull('email_verified_at');
            })
            // ----- Người tạo -----
            ->when($createdBy, fn ($q, $value) => $q->where('created_by', $value))
            ->orderBy($sort, $dir)
            ->orderByDesc('id');

        $users = $query->paginate(10)->withQueryString();

        /* ---------- 3. Gắn cờ quyền cho từng dòng ---------- */
        $users->getCollection()->transform(function (User $user) use ($authUser) {
            $user->setAttribute('can', [
                'update' => $authUser->can('update', $user),
                'delete' => $authUser->can('delete', $user),
                'assignRole' => $authUser->can('assignRole', $user),
                'toggleActive' => $authUser->can('toggleActive', $user),
                'verifyEmail' => $authUser->can('verifyEmail', $user),
                'resetPassword' => $authUser->can('resetPassword', $user),
            ]);
            $user->setAttribute(
                'direct_permissions',
                $user->permissions->pluck('name')->values(),
            );

            // Không bao giờ để lộ hash ra FE
            $user->makeHidden(['password', 'remember_token']);
            $user->unsetRelation('permissions');

            return $user;
        });

        return Inertia::render('admin/users/User', [
            'users' => $users,
            'usersMeta' => [
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
            ],
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', 1)->count(),
                'inactive' => User::where('is_active', 0)->count(),
                'unverified' => User::whereNull('email_verified_at')->count(),
            ],
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->with('permissions:id,name')
                ->orderBy('name')
                ->get(['id', 'name']),
            'permissions' => $authUser->can('manageAccessControl', User::class)
                ? Permission::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name'])
                : [],
            'creators' => User::whereIn('id', User::whereNotNull('created_by')->distinct()->pluck('created_by'))
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'role' => $role,
                // trả '1' / '0' / null để FE khôi phục select tri-state
                'is_active' => is_null($isActive) ? null : (string) $isActive,
                'verified' => is_null($verified) ? null : (string) $verified,
                'created_by' => $createdBy,
                'sort' => $sort,
                'dir' => $dir,
            ],
            'can' => [
                'create' => $authUser->can('create', User::class),
                'manageAccessControl' => $authUser->can('manageAccessControl', User::class),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $data = $this->validateData($request);
        $authUser = $request->user();

        $user = new User;

        if (array_key_exists('roles', $data) || array_key_exists('permissions', $data)) {
            Gate::authorize('assignRole', $user);
        }

        $user = DB::transaction(function () use ($data, $authUser) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $authUser->id,
                'email_verified_at' => $data['mark_verified'] ? now() : null,
            ]);

            $user->syncRoles($data['roles'] ?? []);

            if (array_key_exists('permissions', $data)) {
                $user->syncPermissions($data['permissions']);
            }

            return $user;
        });

        $user->notify(new UserAccountNotification('created', $user));

        return redirect()
            ->route('admin.users.index')
            ->with('flash', Flash::success('user.flash.created', [
                'name' => $user->name,
                'id' => $user->id,
            ]));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $data = $this->validateData($request, $user);
        $authUser = $request->user();
        $previousIsActive = (bool) $user->is_active;
        $previousEmailVerified = (bool) $user->email_verified_at;
        $previousRoles = $user->getRoleNames()->sort()->values()->all();
        $previousPermissions = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();

        if (! is_null($data['is_active']) && $user->is_active !== $data['is_active']) {
            Gate::authorize('toggleActive', $user);
        }

        if (
            ! is_null($data['mark_verified'])
            && $data['mark_verified'] !== (bool) $user->email_verified_at
            && ! $authUser->can('verifyEmail', $user)
        ) {
            abort(403);
        }

        if (! empty($data['password'])) {
            Gate::authorize('resetPassword', $user);
        }

        if (array_key_exists('roles', $data) && ! $authUser->can('assignRole', $user)) {
            $requestedRoles = $data['roles'];
            $currentRoles = $user->getRoleNames()->all();
            sort($requestedRoles);
            sort($currentRoles);
            abort_if($requestedRoles !== $currentRoles, 403);
        }

        if (array_key_exists('permissions', $data)) {
            Gate::authorize('assignRole', $user);
        }

        DB::transaction(function () use ($user, $data, $authUser) {
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ];

            if (! is_null($data['is_active'])) {
                $payload['is_active'] = $data['is_active'];
            }

            // Đổi mật khẩu là tuỳ chọn khi cập nhật
            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            // Cho phép đánh dấu / huỷ đánh dấu xác minh email
            if (! is_null($data['mark_verified'])) {
                $payload['email_verified_at'] = $data['mark_verified']
                    ? ($user->email_verified_at ?? now())
                    : null;
            }

            // Email đổi => bắt buộc xác minh lại
            if ($user->email !== $data['email'] && ! $data['mark_verified']) {
                $payload['email_verified_at'] = null;
            }

            $user->update($payload);

            // Chỉ đồng bộ role khi người thao tác có quyền
            if (array_key_exists('roles', $data) && $authUser->can('assignRole', $user)) {
                $user->syncRoles($data['roles'] ?? []);
            }

            if (array_key_exists('permissions', $data)) {
                $user->syncPermissions($data['permissions']);
            }
        });

        $user->refresh();

        if (! is_null($data['is_active']) && $previousIsActive !== $user->is_active) {
            $user->notify(new UserAccountNotification(
                $user->is_active ? 'activated' : 'deactivated',
                $user,
            ));
        }

        if (! is_null($data['mark_verified']) && ! $previousEmailVerified && $user->email_verified_at) {
            $user->notify(new UserAccountNotification('email_verified', $user));
        }

        $currentRoles = $user->getRoleNames()->sort()->values()->all();
        $currentPermissions = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();

        if ($previousRoles !== $currentRoles || $previousPermissions !== $currentPermissions) {
            $user->notify(new UserAccountNotification('access_updated', $user));
        }

        return back()->with('flash', Flash::success('user.flash.updated', [
            'name' => $user->name,
            'id' => $user->id,
        ]));
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('delete', $user);

        // Chặn tự xoá chính mình (lớp bảo vệ thứ 2, ngoài Policy)
        abort_if($user->id === $request->user()->id, 403, 'Không thể xoá chính tài khoản đang đăng nhập.');

        $name = $user->name;
        $id = $user->id;

        DB::transaction(function () use ($user) {
            $user->syncRoles([]);
            $user->delete();
        });

        return back(303)->with('flash', Flash::success('user.flash.deleted', [
            'name' => $name,
            'id' => $id,
        ]));
    }

    // TOGGLE ACTIVE (khoá / mở khoá nhanh)
    public function toggleActive(Request $request, User $user)
    {
        Gate::authorize('toggleActive', $user);

        abort_if($user->id === $request->user()->id, 403, 'Không thể tự khoá tài khoản của bạn.');

        $user->update(['is_active' => ! $user->is_active]);
        $user->notify(new UserAccountNotification(
            $user->is_active ? 'activated' : 'deactivated',
            $user,
        ));

        return back(303)->with('flash', $user->is_active
            ? Flash::success('user.flash.activated', ['name' => $user->name])
            : Flash::warning('user.flash.deactivated', ['name' => $user->name]));
    }

    // VERIFY EMAIL thủ công
    public function verifyEmail(User $user)
    {
        Gate::authorize('verifyEmail', $user);

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->notify(new UserAccountNotification('email_verified', $user));
        }

        return back(303)->with('flash', Flash::success('user.flash.verified', ['name' => $user->name]));
    }

    // BULK: activate / deactivate / delete
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'user_ids' => ['required', 'array', 'min:1', 'max:100'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $authUser = $request->user();
        $ids = array_values(array_diff($validated['user_ids'], [$authUser->id])); // loại chính mình

        if (empty($ids)) {
            return back(303)->with('flash', Flash::warning('user.flash.bulkEmpty'));
        }

        $targets = User::whereIn('id', $ids)->get();
        $affected = 0;

        DB::transaction(function () use ($targets, $validated, $authUser, &$affected) {
            foreach ($targets as $target) {
                $ability = $validated['action'] === 'delete' ? 'delete' : 'toggleActive';

                if ($authUser->cannot($ability, $target)) {
                    continue; // bỏ qua, không abort cả lô
                }

                match ($validated['action']) {
                    'activate' => $target->update(['is_active' => 1]),
                    'deactivate' => $target->update(['is_active' => 0]),
                    'delete' => tap($target, fn ($u) => $u->syncRoles([]))->delete(),
                };

                $affected++;
            }
        });

        $key = match ($validated['action']) {
            'activate' => 'user.flash.bulkActivated',
            'deactivate' => 'user.flash.bulkDeactivated',
            default => 'user.flash.bulkDeleted',
        };

        return back(303)->with('flash', $affected > 0
            ? Flash::success($key, ['count' => $affected])
            : Flash::warning('user.flash.bulkNoPermission'));
    }

    // HELPERS
    private function validateData(Request $request, ?User $user = null): array
    {
        $isUpdate = (bool) $user;

        if ($request->has('is_active')) {
            $request->merge(['is_active' => $request->boolean('is_active')]);
        } elseif (! $isUpdate) {
            $request->merge(['is_active' => true]);
        }

        if ($request->has('mark_verified')) {
            $request->merge(['mark_verified' => $request->boolean('mark_verified')]);
        } elseif (! $isUpdate) {
            $request->merge(['mark_verified' => false]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string', 'min:8', 'max:100', 'confirmed',
            ],
            'is_active' => ['sometimes', 'boolean'],
            'mark_verified' => ['sometimes', 'nullable', 'boolean'],

            'roles' => ['sometimes', 'array'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
                $this->assignableRole(),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ], [
            'email.unique' => 'Email này đã được sử dụng.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => isset($validated['phone']) ? trim($validated['phone']) : null,
            'password' => $validated['password'] ?? null,
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : null,
        ];

        if (array_key_exists('mark_verified', $validated)) {
            $data['mark_verified'] = (bool) $validated['mark_verified'];
        } else {
            $data['mark_verified'] = null;
        }

        if (array_key_exists('roles', $validated)) {
            $data['roles'] = array_values(array_unique($validated['roles']));
        }

        if (array_key_exists('permissions', $validated)) {
            $data['permissions'] = array_values(array_unique($validated['permissions']));
        }

        return $data;
    }

    // Chỉ admin mới được gán các role bảo vệ (admin).
    private function assignableRole(): \Closure
    {
        return function (string $attribute, $value, $fail) {
            if (in_array($value, self::PROTECTED_ROLES, true) && ! auth()->user()->hasRole('admin')) {
                $fail("Bạn không có quyền gán vai trò \"{$value}\".");
            }
        };
    }
}
