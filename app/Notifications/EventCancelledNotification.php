<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
        public string $reason = '',
    ) {}

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
            'type' => 'event_cancelled',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_name' => $this->event->host?->name,
            'reason' => $this->reason,
            'message' => '"'.$this->event->name.'" was cancelled.',
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
