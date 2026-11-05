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
    $firstAvailableMonth = now()->startOfYear()->subYears(4);
    $outsideAvailablePeriod = $firstAvailableMonth->copy()->subMonth();

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
    Trip::factory()->create([
        'car_id' => $car->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_CONFIRMED,
        'day' => $firstAvailableMonth->toDateString(),
        'distance' => 100,
        'total_fee' => 70000,
    ]);
    Trip::factory()->create([
        'car_id' => $secondCar->id,
        'driver_id' => $driver->id,
        'advisor_id' => $advisor->id,
        'status' => Trip::STATUS_CONFIRMED,
        'day' => $outsideAvailablePeriod->toDateString(),
        'distance' => 10000,
        'total_fee' => 80000,
    ]);

    $currentMonthIndex = (int) $firstAvailableMonth->diffInMonths($currentMonth);
    $previousMonthIndex = $currentMonthIndex - 1;
    $twelveMonthsAgoIndex = $currentMonthIndex - 12;

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('currentMonth', $currentMonth->format('Y-m'))
            ->where('summary.trips', 3)
            ->where('summary.expenses', 60000)
            ->where('summary.distance', 9000)
            ->where('summary.alerts', 1)
            ->where('monthlyStats.0.month', $firstAvailableMonth->format('Y-m'))
            ->where('monthlyStats.0.tripCount', 1)
            ->where('monthlyStats.0.expenseTotal', 70000)
            ->where("monthlyStats.{$twelveMonthsAgoIndex}.tripCount", 1)
            ->where("monthlyStats.{$previousMonthIndex}.tripCount", 1)
            ->where("monthlyStats.{$currentMonthIndex}.tripCount", 3)
            ->where("monthlyStats.{$currentMonthIndex}.expenseTotal", 60000)
            ->has('monthlyStats', $currentMonthIndex + 1)
            ->has('carDistanceSeries', 2)
            ->where('carDistanceSeries', fn ($series): bool => $series->contains(
                fn (array $carStats): bool => $carStats['carId'] === $car->id
                    && $carStats['tripCounts'][0] === 1
                    && $carStats['tripCounts'][$currentMonthIndex] === 2
                    && (float) $carStats['expenseTotals'][$currentMonthIndex] === 30000.0,
            ))
            ->has('distanceAlerts', 2)
            ->where('distanceAlerts.0.distance', 9000)
            ->where('distanceWarningThreshold', 4300)
        );
});
