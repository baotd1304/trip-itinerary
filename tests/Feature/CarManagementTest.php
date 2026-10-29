<?php

use App\Models\Car;
use App\Models\Trip;
use App\Models\User;
use Spatie\Permission\Models\Role;

function createCarManagementAdmin(): User
{
    return User::factory()->create()->assignRole(
        Role::findOrCreate('admin', 'web'),
    );
}

it('lists cars with filters, statistics, and pagination metadata', function () {
    $admin = createCarManagementAdmin();
    $matchingCar = Car::factory()->create([
        'name' => 'Family Sedan',
        'brand' => 'Honda',
        'is_active' => true,
    ]);
    Car::factory()->create([
        'name' => 'Work Van',
        'brand' => 'Toyota',
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.cars.index', ['search' => 'Honda', 'is_active' => '1']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Car')
            ->has('cars.data', 1)
            ->where('cars.data.0.id', $matchingCar->id)
            ->where('cars.data.0.name', 'Family Sedan')
            ->where('stats.total', 2)
            ->where('stats.active', 1)
            ->where('filters.is_active', '1'));
});

it('allows an administrator to create and update a car', function () {
    $admin = createCarManagementAdmin();
    $carData = [
        'name' => 'City Car',
        'brand' => 'Honda',
        'model' => 'Civic',
        'year' => 2024,
        'license_plate' => '51A-12345',
        'owner' => 'Operations',
        'is_active' => true,
    ];

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), $carData)
        ->assertRedirect();

    $car = Car::query()->where('license_plate', '51A-12345')->firstOrFail();

    expect($car->name)->toBe('City Car')
        ->and($car->brand)->toBe('Honda')
        ->and($car->year)->toBe(2024)
        ->and($car->is_active)->toBeTrue();

    $this->put(route('admin.cars.update', $car), [
        ...$carData,
        'name' => 'Updated Car',
        'license_plate' => $car->license_plate,
        'is_active' => false,
    ])->assertRedirect();

    expect($car->refresh()->name)->toBe('Updated Car')
        ->and($car->is_active)->toBeFalse();
});

it('validates unique license plates and supported model years', function () {
    $admin = createCarManagementAdmin();
    Car::factory()->create(['license_plate' => '51B-98765']);

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), [
            'name' => 'Duplicate Plate',
            'license_plate' => '51B-98765',
            'year' => 1800,
        ])
        ->assertSessionHasErrors(['license_plate', 'year']);
});

it('allows authorized administrators to toggle status and run bulk operations', function () {
    $admin = createCarManagementAdmin();
    $car = Car::factory()->create(['is_active' => true]);
    $anotherCar = Car::factory()->create(['is_active' => false]);

    $this->actingAs($admin)
        ->patch(route('admin.cars.toggle-active', $car))
        ->assertRedirect();

    expect($car->refresh()->is_active)->toBeFalse();

    $this->post(route('admin.cars.bulk'), [
        'action' => 'activate',
        'car_ids' => [$car->id, $anotherCar->id],
    ])->assertRedirect();

    expect($car->refresh()->is_active)
        ->toBeTrue()
        ->and($anotherCar->refresh()->is_active)
        ->toBeTrue();
});

it('protects cars referenced by trips from deletion', function () {
    $admin = createCarManagementAdmin();
    $car = Car::factory()->create();
    $advisor = User::factory()->create();
    $driver = User::factory()->create();
    Trip::factory()->create([
        'car_id' => $car->id,
        'advisor_id' => $advisor->id,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.cars.destroy', $car))
        ->assertSessionHasErrors('car');

    $this->assertModelExists($car);
});

it('denies car management to users without the matching car permissions', function () {
    $manager = User::factory()->create()->assignRole(
        Role::findOrCreate('manager', 'web'),
    );

    $this->actingAs($manager)
        ->get(route('admin.cars.index'))
        ->assertForbidden();

    $this->post(route('admin.cars.store'), [
        'name' => 'Restricted car',
        'license_plate' => '51C-00001',
    ])->assertForbidden();

    $this->assertDatabaseMissing('cars', ['license_plate' => '51C-00001']);
});
