<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'phone' => fake()->unique()->e164PhoneNumber(),
            'password' => Hash::make('password'),
        ]);
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $driver = User::firstOrCreate([
            'name' => 'Driver',
            'email' => 'driver@gmail.com',
            'phone' => fake()->unique()->e164PhoneNumber(),
            'password' => Hash::make('password'),
        ]);
        if (! $driver->hasRole('driver')) {
            $driver->assignRole('driver');
        }

        $advisor = User::firstOrCreate([
            'name' => 'Advisor',
            'email' => 'advisor@gmail.com',
            'phone' => fake()->unique()->e164PhoneNumber(),
            'password' => Hash::make('password'),
        ]);
        if (! $advisor->hasRole('advisor')) {
            $advisor->assignRole('advisor');
        }
        
        $editor = User::firstOrCreate([
            'name' => 'Editor',
            'email' => 'editor@gmail.com',
            'phone' => fake()->unique()->e164PhoneNumber(),
            'password' => Hash::make('password'),
        ]);
        if (! $editor->hasRole('editor')) {
            $editor->assignRole('editor');
        }

        User::factory()->count(20)->advisor()->create();
        User::factory()->count(10)->editor()->create();
        User::factory()->count(10)->driver()->create();

    }
}