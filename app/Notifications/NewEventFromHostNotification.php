<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEventFromHostNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
    ) {}

    /**
     * @return list<string>
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
        $this->event->loadMissing('host');
        $hostName = $this->event->host?->name ?? 'a host you follow';

        return [
            'category' => \App\Enums\AttendeeNotificationCategory::Event->value,
            'type' => 'new_event',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_id' => $this->event->hosted_by,
            'host_name' => $this->event->host?->name,
            'event_date' => $this->event->date,
            'event_time' => $this->event->time,
            'message' => 'New event from '.$hostName.': "'.$this->event->name.'".',
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
