<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AttendeeActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public AttendeeNotificationCategory $category,
        public string $type,
        public string $message,
        public string $url,
        public array $metadata = [],
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
        return [
            'category' => $this->category->value,
            'type' => $this->type,
            'message' => $this->message,
            'url' => $this->url,
            ...$this->metadata,
        ];
    }
}
