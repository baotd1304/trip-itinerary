<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolePivot = $columnNames['role_pivot_key'] ?? 'role_id';
        $permissionPivot = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $permissions = [
            'cars.view-any',
            'cars.view',
            'cars.create',
            'cars.update',
            'cars.delete',
            'cars.toggle-active',
        ];
        $now = now();

        foreach ($permissions as $permission) {
            DB::table($tableNames['permissions'])->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table($tableNames['permissions'])
            ->where('guard_name', 'web')
            ->whereIn('name', $permissions)
            ->pluck('id');

        $roleIds = DB::table($tableNames['roles'])
            ->where('guard_name', 'web')
            ->whereIn('name', ['admin', 'manager', 'editor'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table($tableNames['role_has_permissions'])->insertOrIgnore([
                    $permissionPivot => $permissionId,
                    $rolePivot => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $permissionPivot = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $permissions = [
            'cars.view-any',
            'cars.view',
            'cars.create',
            'cars.update',
            'cars.delete',
            'cars.toggle-active',
        ];
        $permissionIds = DB::table($tableNames['permissions'])
            ->where('guard_name', 'web')
            ->whereIn('name', $permissions)
            ->pluck('id');

        DB::table($tableNames['role_has_permissions'])
            ->whereIn($permissionPivot, $permissionIds)
            ->delete();
        DB::table($tableNames['model_has_permissions'])
            ->whereIn($permissionPivot, $permissionIds)
            ->delete();
        DB::table($tableNames['permissions'])
            ->whereIn('id', $permissionIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
