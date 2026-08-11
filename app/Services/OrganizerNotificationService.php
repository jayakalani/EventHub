<?php

namespace App\Services;

use App\Enums\OrganizerNotificationCategory;
use App\Models\Event;
use App\Models\Rating;
use App\Models\ticketCategory;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\OrganizerActivityNotification;
use Carbon\Carbon;

class OrganizerNotificationService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(
        ?User $user,
        OrganizerNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        if (! $user || ! $this->isOrganizer($user)) {
            return;
        }

        $user->notify(new OrganizerActivityNotification(
            $category,
            $type,
            $message,
            $url,
            $metadata,
        ));
    }

    /**
     * Notify the organizer who owns the event (created_by).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function notifyEventOrganizer(
        Event $event,
        OrganizerNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        $event->loadMissing('organizer.userRole');

        $this->send(
            $event->organizer,
            $category,
            $type,
            $message,
            $url,
            array_merge(['event_id' => $event->id], $metadata),
        );
    }

    public function notifyTicketCategorySoldOut(ticketCategory $category): void
    {
        $category->loadMissing('event.organizer.userRole');

        $event = $category->event;

        if (! $event || (int) $category->no_of_available_tickets > 0) {
            return;
        }

        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->unreadNotifications()
            ->where('data->type', 'ticket_category_sold_out')
            ->where('data->ticket_category_id', $category->id)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Ticket,
            'ticket_category_sold_out',
            'Ticket category "'.$category->name.'" for "'.$event->name.'" is sold out.',
            route('organizer.events.show', $event),
            [
                'event_id' => $event->id,
                'ticket_category_id' => $category->id,
                'ticket_category_name' => $category->name,
            ],
        );
    }

    public function notifyEventStartsTomorrow(Event $event): void
    {
        $event->loadMissing('organizer.userRole');
        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->notifications()
            ->where('data->type', 'event_starts_tomorrow')
            ->where('data->event_id', $event->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Reminder,
            'event_starts_tomorrow',
            'Reminder: your event "'.$event->name.'" starts tomorrow.',
            route('organizer.events.show', $event),
            ['event_id' => $event->id],
        );
    }

    public function notifyEventStartsInOneHour(Event $event): void
    {
        $event->loadMissing('organizer.userRole');
        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->notifications()
            ->where('data->type', 'event_starts_in_one_hour')
            ->where('data->event_id', $event->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Reminder,
            'event_starts_in_one_hour',
            'Reminder: your event "'.$event->name.'" starts in 1 hour.',
            route('organizer.events.show', $event),
            ['event_id' => $event->id],
        );
    }

    public function notifyTicketSalesClosingSoon(ticketCategory $category): void
    {
        $category->loadMissing('event.organizer.userRole');

        $event = $category->event;

        if (! $event || ! $category->booking_end) {
            return;
        }

        if (! in_array($event->status, [Event::STATUS_UPCOMING, Event::STATUS_ONGOING], true)) {
            return;
        }

        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->notifications()
            ->where('data->type', 'ticket_sales_closing_soon')
            ->where('data->ticket_category_id', $category->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $closesAt = $category->booking_end->timezone(config('app.timezone'))->format('M j, g:i A');

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Reminder,
            'ticket_sales_closing_soon',
            'Ticket sales for "'.$category->name.'" on "'.$event->name.'" close soon ('.$closesAt.').',
            route('organizer.events.show', $event),
            [
                'event_id' => $event->id,
                'ticket_category_id' => $category->id,
                'ticket_category_name' => $category->name,
                'booking_end' => $category->booking_end->toIso8601String(),
            ],
        );
    }

    /**
     * Remind the organizer to mark an ongoing event as completed after the event date has passed.
     *
     * @return bool True when a notification was sent
     */
    public function notifyMarkEventCompleted(Event $event): bool
    {
        $event->loadMissing('organizer.userRole');

        if ($event->status !== Event::STATUS_ONGOING || ! $event->hasPassed()) {
            return false;
        }

        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return false;
        }

        $alreadySentToday = $organizer->notifications()
            ->where('data->type', 'mark_event_completed')
            ->where('data->event_id', $event->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySentToday) {
            return false;
        }

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Reminder,
            'mark_event_completed',
            'Your event "'.$event->name.'" date has passed. Please change its status from Ongoing to Completed.',
            route('organizer.events.index', ['status' => Event::STATUS_ONGOING]),
            ['event_id' => $event->id],
        );

        return true;
    }

    /**
     * Daily digest of new post-event ratings for an organizer-owned event.
     *
     * @return bool True when a digest notification was sent
     */
    public function notifyReviewDigest(Event $event): bool
    {
        $event->loadMissing('organizer.userRole');
        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return false;
        }

        if ($event->status !== Event::STATUS_COMPLETED || ! $event->date || $event->hasDateYetToBeScheduled()) {
            return false;
        }

        $hoursSinceStart = $event->startsAt()->diffInHours(now());

        // Digests begin after the attendee rating-nudge window (~24h after start).
        if ($hoursSinceStart < 24) {
            return false;
        }

        $alreadySentToday = $organizer->notifications()
            ->where('data->type', 'event_review_digest')
            ->where('data->event_id', $event->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySentToday) {
            return false;
        }

        $lastDigestAt = $organizer->notifications()
            ->where('data->type', 'event_review_digest')
            ->where('data->event_id', $event->id)
            ->latest()
            ->value('created_at');

        $since = $lastDigestAt
            ? Carbon::parse($lastDigestAt)
            : $event->startsAt()->copy()->addHours(23);

        $newRatings = Rating::query()
            ->where('event_id', $event->id)
            ->where('created_at', '>', $since)
            ->get(['id', 'score']);

        $newCount = $newRatings->count();

        if ($newCount < 1) {
            return false;
        }

        $newAvg = round((float) $newRatings->avg('score'), 1);
        $overallAvg = round((float) Rating::query()->where('event_id', $event->id)->avg('score'), 1);
        $overallCount = (int) Rating::query()->where('event_id', $event->id)->count();

        $reviewWord = $newCount === 1 ? 'review' : 'reviews';
        $message = sprintf(
            'Your event "%s" has %d new %s (avg %s/5). Overall: %s/5 from %d %s.',
            $event->name,
            $newCount,
            $reviewWord,
            number_format($newAvg, 1),
            number_format($overallAvg, 1),
            $overallCount,
            $overallCount === 1 ? 'review' : 'reviews',
        );

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Feedback,
            'event_review_digest',
            $message,
            route('organizer.reviews.index', [
                'event_id' => $event->id,
                'from_date' => $since->toDateString(),
            ]),
            [
                'event_id' => $event->id,
                'new_count' => $newCount,
                'new_average' => $newAvg,
                'overall_average' => $overallAvg,
                'overall_count' => $overallCount,
                'since' => $since->toIso8601String(),
            ],
        );

        return true;
    }

    /**
     * @param  array{
     *     weekLabel: string,
     *     from: string,
     *     to: string,
     *     netRevenue: float,
     *     ticketsSold: int,
     *     attendanceRate: float|null,
     *     checkedIn: int,
     *     topEvent: array{name: string, revenue: float, tickets_sold: int}|null,
     *     bottomEvent: array{name: string, revenue: float, tickets_sold: int}|null,
     *     reportsUrl: string
     * }  $digest
     */
    public function notifyWeeklyDigest(User $organizer, array $digest): bool
    {
        if (! $this->isOrganizer($organizer)) {
            return false;
        }

        $alreadySentToday = $organizer->notifications()
            ->where('data->type', 'weekly_performance_digest')
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadySentToday) {
            return false;
        }

        $message = sprintf(
            'This week: LKR %s net · %s tickets sold%s.',
            number_format($digest['netRevenue'], 0),
            number_format($digest['ticketsSold']),
            $digest['topEvent']
                ? ' · top event “'.$digest['topEvent']['name'].'”'
                : ''
        );

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Feedback,
            'weekly_performance_digest',
            $message,
            $digest['reportsUrl'],
            [
                'week_label' => $digest['weekLabel'],
                'from' => $digest['from'],
                'to' => $digest['to'],
                'net_revenue' => $digest['netRevenue'],
                'tickets_sold' => $digest['ticketsSold'],
                'attendance_rate' => $digest['attendanceRate'],
                'checked_in' => $digest['checkedIn'],
                'top_event' => $digest['topEvent'],
                'bottom_event' => $digest['bottomEvent'],
            ],
        );

        return true;
    }

    public function isOrganizer(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::ORGANIZER;
    }
}
