<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripReviewed extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Trip $trip) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $confirmed = $this->trip->status === Trip::STATUS_CONFIRMED;

        return [
            'type' => $confirmed ? 'trip_confirmed' : 'trip_rejected',
            'title_key' => $confirmed
                ? 'notifications.trip.confirmed'
                : 'notifications.trip.rejected',
            'title_params' => ['id' => $this->trip->id],
            'details' => $this->trip->reject_reason,
            'url' => route('client.trips.index'),
        ];
    }
}
