<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventRescheduledNotification extends Notification
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
            'type' => 'event_rescheduled',
            'title' => 'Event Rescheduled',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_name' => $this->event->host?->name,
            'new_date' => $dateLabel,
            'message' => 'Your postponed event "'.$this->event->name.'" has now been rescheduled'.($dateLabel ? ' to '.$dateLabel : '').'.',
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
