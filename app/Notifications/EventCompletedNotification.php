<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->event->loadMissing('host');

        return [
            'category' => AttendeeNotificationCategory::Event->value,
            'type' => 'event_completed',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_name' => $this->event->host?->name,
            'message' => '"'.$this->event->name.'" is now completed. Thanks for attending!',
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
