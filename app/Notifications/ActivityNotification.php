<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    public array $notificationData;

    /**
     * Create a new notification instance.
     *
     * @param array $data [
     *   'activity_type' => 'attendance_check_in'|'attendance_check_out'|'route_created'|'route_started'|'visit_check_in'|'visit_check_out',
     *   'title' => string,
     *   'message' => string,
     *   'actor_id' => string,
     *   'actor_name' => string,
     *   'actor_role' => string,
     *   'info' => string, // e.g. "Sales • 08.02" or "Rute Pamanukan • 5 toko"
     *   'url' => string,
     *   'related_id' => string|null,
     *   'related_type' => string|null,
     *   'activity_time' => string (ISO date/datetime)
     * ]
     */
    public function __construct(array $data)
    {
        $this->notificationData = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->notificationData;
    }
}
