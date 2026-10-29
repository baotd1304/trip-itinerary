<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripReopened extends Notification
{
    use Queueable;

    public function __construct(private readonly Trip $trip) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Trip #{$this->trip->id} was reopened")
            ->line("Trip #{$this->trip->id} was reopened and is ready for editing.")
            ->action('View trips', route('client.trips.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trip_reopened',
            'title_key' => 'notifications.trip.reopened',
            'title_params' => ['id' => $this->trip->id],
            'url' => route('client.trips.index'),
        ];
    }
}
