<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowTicketInventoryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Event $event,
        public int $remaining,
        public int $capacity,
        public float $fillRate,
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
        $message = $this->remaining === 0
            ? '"'.$this->event->name.'" is sold out — no tickets remaining.'
            : 'Low ticket inventory for "'.$this->event->name.'": only '.$this->remaining.' of '.$this->capacity.' left ('.$this->fillRate.'% sold).';

        return [
            'type' => 'low_ticket_inventory',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'remaining' => $this->remaining,
            'capacity' => $this->capacity,
            'fill_rate' => $this->fillRate,
            'message' => $message,
            'url' => route('organizer.events.show', $this->event),
        ];
    }
}
