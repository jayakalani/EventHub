<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventScheduleAnnouncedNotification extends Notification
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

        $dateLabel = $this->event->formattedScheduleDate() ?? $this->event->date;

        return [
            'type' => 'event_schedule_announced',
            'title' => 'Event Date Announced',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_name' => $this->event->host?->name,
            'new_date' => $dateLabel,
            'message' => 'The event "'.$this->event->name.'" now has a scheduled date'
                .($dateLabel ? ': '.$dateLabel : '')
                .($this->event->time ? ' • '.$this->event->time : '')
                .'.',
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
