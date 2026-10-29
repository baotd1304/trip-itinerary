<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class UserAccountNotification extends Notification
{
    public function __construct(
        private readonly string $event,
        private readonly User $user,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $titleKey = match ($this->event) {
            'created' => 'notifications.user.created',
            'activated' => 'notifications.user.activated',
            'deactivated' => 'notifications.user.deactivated',
            'email_verified' => 'notifications.user.emailVerified',
            'access_updated' => 'notifications.user.accessUpdated',
        };

        return [
            'type' => "user_{$this->event}",
            'title_key' => $titleKey,
            'title_params' => ['name' => $this->user->name],
            'url' => route('profile.edit'),
        ];
    }
}
