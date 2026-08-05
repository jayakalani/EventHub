<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Enums\EventReminderTypeEnum;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
        public EventReminderTypeEnum $reminderType,
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
            'category' => AttendeeNotificationCategory::Reminder->value,
            'type' => 'event_reminder',
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'host_name' => $this->event->host?->name,
            'reminder_type' => $this->reminderType->value,
            'reminder_label' => $this->reminderType->label(),
            'message' => $this->reminderType->message($this->event->name),
            'url' => route('attendee.events.show', $this->event),
        ];
    }
}
