<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public Event $event,
        public array $changes,
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

        return [
            'type' => 'event_updated',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_name' => $this->event->host?->name,
            'changes' => $this->changes,
            'message' => 'The organizer updated "'.$this->event->name.'".',
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
