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

        User::factory()->count(20)->advisor()->create();
        User::factory()->count(10)->editor()->create();
        User::factory()->count(10)->driver()->create();
    }
}