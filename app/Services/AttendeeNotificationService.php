<?php

namespace App\Services;

use App\Enums\AttendeeNotificationCategory;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\AttendeeActivityNotification;

class AttendeeNotificationService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(
        ?User $user,
        AttendeeNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        if (! $user || ! $this->isAttendee($user)) {
            return;
        }

        $user->notifyNow(new AttendeeActivityNotification(
            $category,
            $type,
            $message,
            $url,
            $metadata,
        ));
    }

    private function isAttendee(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::ATTENDEE;
    }
}
