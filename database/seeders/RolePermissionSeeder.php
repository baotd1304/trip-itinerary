<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'admin',
            'advisor',
            'driver',
            'editor',
            'manager',
            'partner',
        ];
        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $userPermissions = [
            'users.view-any',
            'users.view',
            'users.create',
            'users.create-admin',
            'users.update',
            'users.update-admin',
            'users.delete',
            'users.delete-admin',
            'users.toggle-active',
            'users.toggle-admin-active',
            'users.assign-role',
            'users.assign-role-admin',
            'users.verify-email',
            'users.reset-password',
            'users.manage-access-control',
        ];

        $tripPermissions = [
            'trips.view-any',
            'trips.view',
            'trips.create',
            'trips.update',
            'trips.delete',
            'trips.request-reopen',
            'trips.review-reopen',
            'trips.review',
        ];

        $viewPermissions = [
            'dashboard.view',
            'setting.view',
            ''
        ];

        $permissions = array_merge(
            $userPermissions,
            $tripPermissions,
            $viewPermissions
        );
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // gan permission cho role
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        $manager = Role::findOrCreate('manager', 'web');
        $manager->syncPermissions([
            'trips.view-any',
            'trips.view',
            'trips.create',
            'trips.update',
            'trips.delete',
            'trips.request-reopen',
            'trips.review-reopen',
            'trips.review',
            'users.view-any',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.toggle-active',
            'users.assign-role',
            'users.verify-email',
            'users.reset-password',
            'users.manage-access-control',
        ]);

        $advisor = Role::findOrCreate('advisor', 'web');
        $advisor->syncPermissions([
            'trips.view',
            'trips.review-reopen',
            'trips.review',
        ]);
        $driver = Role::findOrCreate('driver', 'web');
        $driver->syncPermissions([
            'trips.view',
            'trips.create',
            'trips.update',
            'trips.request-reopen',
        ]);
        
        $editor = Role::findOrCreate('editor', 'web');
        $editor->syncPermissions([
            'trips.view',
            'trips.create',
            'trips.update',
            'trips.request-reopen',
        ]);
        $partner = Role::findOrCreate('partner', 'web');
        $partner->syncPermissions([
            'trips.view',
        ]);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
