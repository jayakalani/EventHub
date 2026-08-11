<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEventFromArtistNotification extends Notification implements ShouldQueue
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
        $this->event->loadMissing('artists');
        $artistName = $this->event->artists->pluck('name')->filter()->implode(', ');
        $artistName = $artistName !== '' ? $artistName : 'an artist you follow';

        return [
            'category' => \App\Enums\AttendeeNotificationCategory::Event->value,
            'type' => 'new_event',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'artist_ids' => $this->event->artists->pluck('id')->values()->all(),
            'artist_names' => $this->event->artists->pluck('name')->values()->all(),
            'event_date' => $this->event->date,
            'event_time' => $this->event->time,
            'message' => 'New event from '.$artistName.': "'.$this->event->name.'".',
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
