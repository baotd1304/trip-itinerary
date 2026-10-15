<?php

namespace App\Notifications;

use App\Models\TripReopenRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReopenRequestReviewed extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly TripReopenRequest $request) {}

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
        $approved = $this->request->status === TripReopenRequest::STATUS_APPROVED;

        return [
            'type' => $approved ? 'reopen_request_approved' : 'reopen_request_rejected',
            'title_key' => $approved
                ? 'notifications.trip.reopenApproved'
                : 'notifications.trip.reopenRejected',
            'title_params' => ['id' => $this->request->trip_id],
            'details' => $this->request->review_note,
            'url' => route('client.trips.index'),
        ];
    }
}
