<?php

use App\Models\Car;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('shows monthly confirmed trip, expense and per-car distance statistics', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
    $driver = User::factory()->create(['is_active' => true]);
    $advisor = User::factory()->create(['is_active' => true]);
    $car = Car::factory()->create();
    $secondCar = Car::factory()->create();

    $currentMonth = now()->startOfMonth();
    $previousMonth = now()->subMonth()->startOfMonth();

    Trip::factory()->create([
        'car_id' => $car->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_CONFIRMED,
        'day' => $currentMonth->toDateString(),
        'distance' => 2800,
        'total_fee' => 10000,
    ]);
    Trip::factory()->create([
        'car_id' => $car->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_CONFIRMED,
        'day' => $currentMonth->copy()->addDays(1)->toDateString(),
        'distance' => 1900,
        'total_fee' => 20000,
    ]);
    Trip::factory()->create([
        'car_id' => $secondCar->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_CONFIRMED,
        'day' => $currentMonth->copy()->addDays(2)->toDateString(),
        'distance' => 4300,
        'total_fee' => 30000,
    ]);
    Trip::factory()->create([
        'car_id' => $car->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_CONFIRMED,
        'day' => $previousMonth->toDateString(),
        'distance' => 1000,
        'total_fee' => 40000,
    ]);
    Trip::factory()->create([
        'car_id' => $car->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_PENDING,
        'day' => $currentMonth->toDateString(),
        'distance' => 9000,
        'total_fee' => 50000,
    ]);
    Trip::factory()->create([
        'car_id' => $secondCar->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_CONFIRMED,
        'day' => now()->subMonths(12)->startOfMonth()->toDateString(),
        'distance' => 9000,
        'total_fee' => 60000,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('summary.trips', 4)
            ->where('summary.expenses', 100000)
            ->where('summary.distance', 10000)
            ->where('summary.alerts', 1)
            ->where('monthlyStats.11.tripCount', 3)
            ->where('monthlyStats.11.expenseTotal', 60000)
            ->has('distanceAlerts', 1)
            ->where('distanceAlerts.0.carId', $car->id)
            ->where('distanceAlerts.0.distance', 4700)
            ->where('distanceAlerts.0.month', $currentMonth->format('Y-m'))
            ->where('distanceWarningThreshold', 4300)
        );
});
