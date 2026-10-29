<?php

use App\Models\Car;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function createExpenseManagementAdmin(): User
{
    return User::factory()->create()->assignRole(
        Role::findOrCreate('admin', 'web'),
    );
}

function makeExpense(array $attributes = []): Expense
{
    return Expense::create([
        'name' => 'Mileage',
        'overkm_rate' => 6500,
        'overtime_rate' => 30000,
        'overnight_rate' => 300000,
        'holiday_rate' => 400000,
        'note' => null,
        'is_active' => true,
        ...$attributes,
    ]);
}

it('lists expenses with filters, creator, statistics, and pagination metadata', function () {
    $admin = createExpenseManagementAdmin();
    $matchingExpense = makeExpense([
        'name' => 'Holiday rate',
        'note' => 'Public holiday',
        'created_by' => $admin->id,
        'is_active' => true,
    ]);
    makeExpense([
        'name' => 'Inactive rate',
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.expenses.index', ['search' => 'Holiday', 'is_active' => '1']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Expense')
            ->has('expenses.data', 1)
            ->where('expenses.data.0.id', $matchingExpense->id)
            ->where('expenses.data.0.name', 'Holiday rate')
            ->where('expenses.data.0.creator.name', $admin->name)
            ->where('stats.total', 2)
            ->where('stats.active', 1)
            ->where('filters.is_active', '1'));
});

it('allows administrators to create and update expenses', function () {
    $admin = createExpenseManagementAdmin();
    $data = [
        'name' => 'Mileage and time',
        'overkm_rate' => 6500,
        'overtime_rate' => 30000,
        'overnight_rate' => 300000,
        'holiday_rate' => 400000,
        'note' => 'Standard rates',
        'is_active' => true,
    ];

    $this->actingAs($admin)
        ->post(route('admin.expenses.store'), $data)
        ->assertRedirect();

    $expense = Expense::query()->where('name', 'Mileage and time')->firstOrFail();

    expect($expense->created_by)->toBe($admin->id)
        ->and($expense->overkm_rate)->toBe(6500)
        ->and($expense->holiday_rate)->toBe(400000)
        ->and($expense->is_active)->toBeTrue();

    $this->put(route('admin.expenses.update', $expense), [
        ...$data,
        'name' => 'Updated rates',
        'overkm_rate' => 7000,
        'note' => 'Updated note',
        'is_active' => false,
    ])->assertRedirect();

    expect($expense->refresh()->name)->toBe('Updated rates')
        ->and($expense->overkm_rate)->toBe(7000)
        ->and($expense->note)->toBe('Updated note')
        ->and($expense->is_active)->toBeFalse();
});

it('validates required names and non-negative expense rates', function () {
    $admin = createExpenseManagementAdmin();

    $this->actingAs($admin)
        ->post(route('admin.expenses.store'), [
            'name' => '',
            'overkm_rate' => -1,
        ])
        ->assertSessionHasErrors(['name', 'overkm_rate']);
});

it('does not allow expense editors to change status without toggle permission', function () {
    $editor = User::factory()->create()->assignRole(
        Role::findOrCreate('editor', 'web'),
    );
    $editor->givePermissionTo(Permission::findOrCreate('expenses.update', 'web'));
    $expense = makeExpense(['is_active' => true]);

    $this->actingAs($editor)
        ->put(route('admin.expenses.update', $expense), [
            'is_active' => false,
        ])
        ->assertSessionHasErrors('is_active');

    expect($expense->refresh()->is_active)->toBeTrue();
});

it('allows administrators to toggle status and run bulk expense operations', function () {
    $admin = createExpenseManagementAdmin();
    $expense = makeExpense(['is_active' => true]);
    $anotherExpense = makeExpense(['name' => 'Another rate', 'is_active' => false]);

    $this->actingAs($admin)
        ->patch(route('admin.expenses.toggle-active', $expense))
        ->assertRedirect();

    expect($expense->refresh()->is_active)->toBeFalse();

    $this->post(route('admin.expenses.bulk'), [
        'action' => 'activate',
        'expense_ids' => [$expense->id, $anotherExpense->id],
    ])->assertRedirect();

    expect($expense->refresh()->is_active)
        ->toBeTrue()
        ->and($anotherExpense->refresh()->is_active)
        ->toBeTrue();
});

it('protects expenses referenced by trips from deletion', function () {
    $admin = createExpenseManagementAdmin();
    $expense = makeExpense();
    $car = Car::factory()->create();
    $advisor = User::factory()->create();
    $driver = User::factory()->create();
    $trip = Trip::factory()->create([
        'car_id' => $car->id,
        'advisor_id' => $advisor->id,
        'driver_id' => $driver->id,
    ]);
    TripExpense::create([
        'trip_id' => $trip->id,
        'expense_id' => $expense->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.expenses.destroy', $expense))
        ->assertSessionHasErrors('expense');

    $this->assertModelExists($expense);
});

it('denies expense management to users without matching permissions', function () {
    $manager = User::factory()->create()->assignRole(
        Role::findOrCreate('manager', 'web'),
    );

    $this->actingAs($manager)
        ->get(route('admin.expenses.index'))
        ->assertForbidden();

    $this->post(route('admin.expenses.store'), ['name' => 'Restricted expense'])
        ->assertForbidden();

    $this->assertDatabaseMissing('expenses', ['name' => 'Restricted expense']);
});
