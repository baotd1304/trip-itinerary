<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\TripReopenRequest;

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
        return [
            'type'       => 'reopen_request_reviewed',
            'trip_id'    => $this->request->trip_id,
            'request_id' => $this->request->id,
            'title'      => $this->request->status === TripReopenRequest::STATUS_APPROVED
                ? "Yêu cầu mở khoá chuyến #{$this->request->trip_id} đã được duyệt"
                : "Yêu cầu mở khoá chuyến #{$this->request->trip_id} bị từ chối",
            'note'  => $this->request->review_note,
            'url'        => route('client.trips.index'),
        ];
    }
}
