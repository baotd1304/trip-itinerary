<?php

use App\Models\User;
use App\Notifications\UserAccountNotification;
use Spatie\Permission\Models\Role;

it('shares the authenticated user notification list and unread total', function () {
    Role::findOrCreate('driver', 'web');
    $user = User::factory()->create()->assignRole('driver');
    $user->notify(new UserAccountNotification('created', $user));

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('notificationCount', 1)
            ->where('unreadNotificationCount', 1)
            ->has('notifications', 1)
            ->where('notifications.0.data.title_key', 'notifications.user.created'));
});

it('loads the authenticated user additional notifications in pages', function () {
    Role::findOrCreate('driver', 'web');
    $user = User::factory()->create()->assignRole('driver');
    $otherUser = User::factory()->create(['name' => 'Other notification owner']);

    foreach (range(1, 25) as $index) {
        $user->notify(new UserAccountNotification('created', $user));
    }

    $otherUser->notify(new UserAccountNotification('created', $otherUser));

    $this->actingAs($user)
        ->getJson(route('notifications.more', ['page' => 2]))
        ->assertSuccessful()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('next_page', 3)
        ->assertJsonPath('data.0.data.title_key', 'notifications.user.created')
        ->assertJsonMissing(['name' => $otherUser->name]);
});

it('allows a user to mark only their own notification as read', function () {
    Role::findOrCreate('driver', 'web');
    $owner = User::factory()->create()->assignRole('driver');
    $otherUser = User::factory()->create();
    $owner->notify(new UserAccountNotification('created', $owner));
    $otherUser->notify(new UserAccountNotification('created', $otherUser));

    $notification = $owner->unreadNotifications()->firstOrFail();
    $otherNotification = $otherUser->unreadNotifications()->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->patch(route('notifications.read', $otherNotification->id))
        ->assertNotFound();

    expect($otherNotification->fresh()->read_at)->toBeNull();
});

it('allows a user to mark all of their notifications as read', function () {
    Role::findOrCreate('driver', 'web');
    $user = User::factory()->create()->assignRole('driver');
    $user->notify(new UserAccountNotification('created', $user));
    $user->notify(new UserAccountNotification('activated', $user));

    $this->actingAs($user)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($user->readNotifications()->count())->toBe(2);
});
