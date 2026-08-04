<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventPostponedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Event $event,
        public string $postponementReason,
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

        $scheduleLine = $this->event->hasDateYetToBeScheduled()
            ? 'Date Yet To Be Scheduled'
            : 'New Date: '.($this->event->formattedScheduleDate() ?? 'TBA');

        return [
            'type' => 'event_postponed',
            'title' => 'Event Postponed',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_name' => $this->event->host?->name,
            'postponement_reason' => $this->postponementReason,
            'date_tba' => $this->event->hasDateYetToBeScheduled(),
            'new_date' => $this->event->hasDateYetToBeScheduled() ? null : $this->event->formattedScheduleDate(),
            'message' => 'The event "'.$this->event->name.'" has been postponed. '.$scheduleLine,
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
