<?php

namespace App\Services;

use App\Enums\AdminNotificationCategory;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\ticketCategory;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\AdminActivityNotification;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(
        ?User $user,
        AdminNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        if (! $user || ! $this->isAdmin($user)) {
            return;
        }

        $user->notify(new AdminActivityNotification(
            $category,
            $type,
            $message,
            $url,
            $metadata,
        ));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function notifyAllAdmins(
        AdminNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        foreach ($this->allAdmins() as $admin) {
            $this->send($admin, $category, $type, $message, $url, $metadata);
        }
    }

    public function notifyCategoryDeleted(EventCategory $category, User $actor): void
    {
        $this->notifyAllAdmins(
            AdminNotificationCategory::Audit,
            'category_deleted',
            'Administrator deleted category "'.$category->name.'".',
            route('admin.event-categories.index'),
            [
                'category_id' => $category->id,
                'actor_id' => $actor->id,
            ],
        );
    }

    public function notifyPaymentSettingsChanged(Event $event, User $actor): void
    {
        $this->notifyAllAdmins(
            AdminNotificationCategory::Audit,
            'payment_settings_changed',
            'Organizer changed payment settings for "'.$event->name.'".',
            route('admin.audit-logs'),
            [
                'event_id' => $event->id,
                'actor_id' => $actor->id,
            ],
        );
    }

    public function notifyAccountLocked(User $user): void
    {
        $this->notifyAllAdmins(
            AdminNotificationCategory::Security,
            'account_locked',
            'Account locked after multiple failed logins: '.$user->email.'.',
            route('admin.users'),
            [
                'user_id' => $user->id,
            ],
        );
    }

    public function notifyLastAdminLockPrevented(User $user): void
    {
        $this->notifyAllAdmins(
            AdminNotificationCategory::Security,
            'last_admin_lock_prevented',
            'Repeated failed logins against the last admin account were ignored to prevent a platform lockout: '.$user->email.'.',
            route('admin.users'),
            [
                'user_id' => $user->id,
            ],
        );
    }

    public function notifyOrganizerCategoryDeleted(Event $event, ticketCategory $ticketCategory, User $actor): void
    {
        $this->notifyAllAdmins(
            AdminNotificationCategory::Security,
            'organizer_category_deleted',
            'Organizer deleted category "'.$ticketCategory->name.'" from event "'.$event->name.'".',
            route('admin.audit-logs'),
            [
                'event_id' => $event->id,
                'ticket_category_id' => $ticketCategory->id,
                'actor_id' => $actor->id,
            ],
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function allAdmins(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('userRole', fn ($q) => $q->where('name_en', UserRole::ADMIN))
            ->get();
    }

    public function isAdmin(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::ADMIN;
    }
}
