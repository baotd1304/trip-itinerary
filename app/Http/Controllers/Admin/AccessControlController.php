<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlController extends Controller
{
    private const GUARD = 'web';

    public function storeRole(Request $request): RedirectResponse
    {
        Gate::authorize('manageAccessControl', User::class);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique((new Role)->getTable(), 'name')->where('guard_name', self::GUARD),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists((new Permission)->getTable(), 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        DB::transaction(function () use ($data): void {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => self::GUARD,
            ]);
            $role->syncPermissions($data['permissions'] ?? []);
        });

        return back()->with('flash', Flash::success('user.flash.roleCreated', ['name' => $data['name']]));
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('manageAccessControl', User::class);
        abort_unless($role->guard_name === self::GUARD, 404);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique($role->getTable(), 'name')
                    ->where('guard_name', self::GUARD)
                    ->ignore($role->id),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists((new Permission)->getTable(), 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        abort_if($role->name === 'admin' && $data['name'] !== 'admin', 403);

        DB::transaction(function () use ($role, $data): void {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);
        });

        return back()->with('flash', Flash::success('user.flash.roleUpdated', ['name' => $data['name']]));
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        Gate::authorize('manageAccessControl', User::class);
        abort_unless($role->guard_name === self::GUARD, 404);
        abort_if($role->name === 'admin', 403, 'The administrator role cannot be deleted.');

        $name = $role->name;
        $role->delete();

        return back()->with('flash', Flash::success('user.flash.roleDeleted', ['name' => $name]));
    }

    public function storePermission(Request $request): RedirectResponse
    {
        Gate::authorize('manageAccessControl', User::class);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique((new Permission)->getTable(), 'name')->where('guard_name', self::GUARD),
            ],
        ]);

        Permission::query()->create([
            'name' => $data['name'],
            'guard_name' => self::GUARD,
        ]);

        return back()->with('flash', Flash::success('user.flash.permissionCreated', ['name' => $data['name']]));
    }

    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        Gate::authorize('manageAccessControl', User::class);
        abort_unless($permission->guard_name === self::GUARD, 404);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique($permission->getTable(), 'name')
                    ->where('guard_name', self::GUARD)
                    ->ignore($permission->id),
            ],
        ]);

        $permission->update(['name' => $data['name']]);

        return back()->with('flash', Flash::success('user.flash.permissionUpdated', ['name' => $data['name']]));
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        Gate::authorize('manageAccessControl', User::class);
        abort_unless($permission->guard_name === self::GUARD, 404);

        $name = $permission->name;

        DB::transaction(function () use ($permission): void {
            $permission->roles()->detach();
            $permission->users()->detach();
            $permission->delete();
        });

        return back()->with('flash', Flash::success('user.flash.permissionDeleted', ['name' => $name]));
    }
}
