<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'advisor']);
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'editor']);

        Permission::firstOrCreate(['name' => 'trip.view']);
        Permission::firstOrCreate(['name' => 'trip.create']);
        Permission::firstOrCreate(['name' => 'trip.update']);
        Permission::firstOrCreate(['name' => 'trip.delete']);
        Permission::firstOrCreate(['name' => 'trip.confirm']);

        //gan permission cho role
        Role::findByName('admin')->syncPermissions([
            'trip.view',
            'trip.create',
            'trip.update',
            'trip.delete',
            'trip.confirm',
        ]);

        Role::findByName('advisor')->syncPermissions([
            'trip.view',
            'trip.confirm'
        ]);
        Role::findByName('driver')->syncPermissions([
            'trip.view',    
            'trip.create',
            'trip.update',
        ]);


    }
}
