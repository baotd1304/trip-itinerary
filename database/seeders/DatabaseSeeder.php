<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Tạo role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Tạo permission
        $viewDashboard = Permission::firstOrCreate(['name' => 'view dashboard']);
        $manageUsers = Permission::firstOrCreate(['name' => 'manage users']);

        // Gán permission cho admin
        $adminRole->syncPermissions([$viewDashboard, $manageUsers]);

        // Tạo user admin nếu chưa có
        $adminUser = User::firstWhere('email', 'admin@example.com');

        if (! $adminUser) {
            $adminUser = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Gán role admin
        $adminUser->assignRole($adminRole);

        // Nếu muốn tạo user thường
        $regularUser = User::firstWhere('email', 'user@example.com');

        if (! $regularUser) {
            $regularUser = User::factory()->create([
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $regularUser->assignRole($userRole);
    }
}
