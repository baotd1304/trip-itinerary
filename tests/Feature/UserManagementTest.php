<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function createUserManagementAdmin(): User
{
    Role::findOrCreate('admin', 'web');

    return User::factory()->create()->assignRole('admin');
}

it('prevents users from deleting, deactivating, or changing their own roles', function () {
    $admin = createUserManagementAdmin();

    expect(Gate::forUser($admin)->allows('delete', $admin))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('toggleActive', $admin))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('assignRole', $admin))->toBeFalse();
});

it('checks the matching permission when managing regular and admin users', function () {
    $actor = User::factory()->create();
    $user = User::factory()->create(['email_verified_at' => null]);
    $admin = createUserManagementAdmin();

    foreach ([
        'users.update',
        'users.update-admin',
        'users.delete',
        'users.delete-admin',
        'users.toggle-active',
        'users.toggle-admin-active',
        'users.assign-role',
        'users.assign-role-admin',
        'users.verify-email',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $actor->givePermissionTo([
        'users.update',
        'users.delete',
        'users.toggle-active',
        'users.assign-role',
        'users.verify-email',
    ]);

    expect(Gate::forUser($actor)->allows('update', $user))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('delete', $user))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('toggleActive', $user))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('verifyEmail', $user))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('assignRole', $user))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('update', $admin))->toBeFalse()
        ->and(Gate::forUser($actor)->allows('delete', $admin))->toBeFalse()
        ->and(Gate::forUser($actor)->allows('toggleActive', $admin))->toBeFalse()
        ->and(Gate::forUser($actor)->allows('assignRole', $admin))->toBeFalse();

    $actor->givePermissionTo([
        'users.update-admin',
        'users.delete-admin',
        'users.toggle-admin-active',
        'users.assign-role-admin',
    ]);

    expect(Gate::forUser($actor)->allows('update', $admin))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('delete', $admin))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('toggleActive', $admin))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('assignRole', $admin))->toBeTrue();
});

it('allows authorized users to verify only accounts with an unverified email', function () {
    $manager = User::factory()->create()->assignRole(
        Role::findOrCreate('manager', 'web'),
    );
    $manager->givePermissionTo([
        Permission::findOrCreate('users.update', 'web'),
        Permission::findOrCreate('users.toggle-active', 'web'),
        Permission::findOrCreate('users.verify-email', 'web'),
    ]);
    $user = User::factory()->create([
        'is_active' => true,
        'email_verified_at' => null,
    ]);

    expect(Gate::forUser($manager)->allows('verifyEmail', $user))->toBeTrue();

    $this->actingAs($manager)
        ->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => false,
            'mark_verified' => true,
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->is_active)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Gate::forUser($manager)->allows('verifyEmail', $user))->toBeFalse();

    $this->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'is_active' => true,
        'mark_verified' => false,
    ])->assertForbidden();

    $user->refresh();

    expect($user->is_active)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();

    $admin = createUserManagementAdmin();

    expect(Gate::forUser($admin)->allows('verifyEmail', $user))->toBeFalse();
});

it('allows an administrator to create users with roles and direct permissions', function () {
    $admin = createUserManagementAdmin();
    Role::findOrCreate('advisor', 'web');
    Permission::findOrCreate('trips.view', 'web');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'phone' => '0900000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
            'mark_verified' => false,
            'roles' => ['advisor'],
            'permissions' => ['trips.view'],
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

    expect($user->phone)->toBe('0900000000')
        ->and($user->created_by)->toBe($admin->id)
        ->and($user->hasRole('advisor'))->toBeTrue()
        ->and($user->hasDirectPermission('trips.view'))->toBeTrue();

    expect($user->notifications()->sole()->data['title_key'])
        ->toBe('notifications.user.created');
});

it('prevents a manager from assigning roles while creating a user', function () {
    $managerRole = Role::findOrCreate('manager', 'web');
    Role::findOrCreate('advisor', 'web');
    $manager = User::factory()->create()->assignRole($managerRole);

    $this->actingAs($manager)
        ->post(route('admin.users.store'), [
            'name' => 'Restricted User',
            'email' => 'restricted-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['advisor'],
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'restricted-user@example.com']);
});

it('allows administrators to update a user and synchronize roles and direct permissions', function () {
    $admin = createUserManagementAdmin();
    $editor = Role::findOrCreate('editor', 'web');
    $oldPermission = Permission::findOrCreate('trips.view', 'web');
    Permission::findOrCreate('trips.update', 'web');
    $user = User::factory()->create([
        'is_active' => false,
        'email_verified_at' => null,
    ]);
    $user->assignRole($editor);
    $user->givePermissionTo($oldPermission);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'name' => 'Updated User',
            'email' => 'updated-user@example.com',
            'phone' => '0911111111',
            'is_active' => true,
            'mark_verified' => true,
            'roles' => ['editor'],
            'permissions' => ['trips.update'],
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Updated User')
        ->and($user->email)->toBe('updated-user@example.com')
        ->and($user->phone)->toBe('0911111111')
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole('editor'))->toBeTrue()
        ->and($user->hasDirectPermission('trips.view'))->toBeFalse()
        ->and($user->hasDirectPermission('trips.update'))->toBeTrue();

    expect($user->notifications()->get()->pluck('data.title_key')->sort()->values()->all())
        ->toBe([
            'notifications.user.accessUpdated',
            'notifications.user.activated',
            'notifications.user.emailVerified',
        ]);
});

it('allows administrators to delete other users', function () {
    $admin = createUserManagementAdmin();
    $role = Role::findOrCreate('advisor', 'web');
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('allows only administrators to manage roles and permissions', function () {
    $admin = createUserManagementAdmin();
    $permission = Permission::findOrCreate('trips.view', 'web');
    $adminRole = Role::findByName('admin', 'web');

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $adminRole), [
            'name' => 'super-admin',
            'permissions' => [],
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'trip-reviewer',
            'permissions' => ['trips.view'],
        ])
        ->assertRedirect();

    expect(Role::findByName('trip-reviewer', 'web')->hasPermissionTo('trips.view'))->toBeTrue();

    $role = Role::findByName('trip-reviewer', 'web');
    $this->actingAs($admin)
        ->put(route('admin.roles.update', $role), [
            'name' => 'trip-manager',
            'permissions' => [],
        ])
        ->assertRedirect();

    expect(Role::findByName('trip-manager', 'web')->permissions()->whereKey($permission->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.permissions.store'), ['name' => 'users.manage'])
        ->assertRedirect();

    $permission = Permission::findByName('users.manage', 'web');
    $this->actingAs($admin)
        ->put(route('admin.permissions.update', $permission), ['name' => 'users.edit'])
        ->assertRedirect();

    $this->assertDatabaseHas('permissions', ['name' => 'users.edit', 'guard_name' => 'web']);

    $permission = Permission::findByName('users.edit', 'web');
    $this->actingAs($admin)
        ->delete(route('admin.permissions.destroy', $permission))
        ->assertRedirect();

    $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);

    $manager = User::factory()->create()->assignRole(Role::findOrCreate('manager', 'web'));

    $this->actingAs($manager)
        ->post(route('admin.permissions.store'), ['name' => 'users.manage'])
        ->assertForbidden();

    $this->assertDatabaseMissing('permissions', ['name' => 'users.manage']);
});
