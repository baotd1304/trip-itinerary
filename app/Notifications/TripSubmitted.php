<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Notifications\Notification;

class TripSubmitted extends Notification
{
    public function __construct(private readonly Trip $trip) {}

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
        return [
            'type' => 'trip_submitted',
            'title_key' => 'notifications.trip.submitted',
            'title_params' => ['id' => $this->trip->id],
            'url' => route('client.trips.index', ['status' => Trip::STATUS_PENDING]),
        ];
    }
}
