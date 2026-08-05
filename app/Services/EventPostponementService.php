<?php

namespace App\Services;

use App\Mail\EventPostponedMail;
use App\Mail\EventRescheduledMail;
use App\Mail\EventScheduleAnnouncedMail;
use App\Models\Event;
use App\Notifications\EventPostponedNotification;
use App\Notifications\EventRescheduledNotification;
use App\Notifications\EventScheduleAnnouncedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class EventPostponementService
{
    public function __construct(
        protected EventNotificationService $eventNotificationService,
        protected AuditLogService $auditLogService,
    ) {}

    public function postpone(
        Event $event,
        string $reason,
        ?string $newDate = null,
        ?string $newTime = null,
        bool $notifyEmail = true,
        bool $notifyInApp = true,
    ): void {
        if (! $event->canBePostponed()) {
            throw new RuntimeException('Only upcoming events can be postponed.');
        }

        if ($event->isCancelled()) {
            throw new RuntimeException('Cancelled events cannot be postponed.');
        }

        if ($event->isCompleted()) {
            throw new RuntimeException('Completed events cannot be postponed.');
        }

        if ($event->status === Event::STATUS_ONGOING) {
            throw new RuntimeException('Ongoing events cannot be postponed.');
        }

        $hasNewDate = filled($newDate);

        DB::transaction(function () use ($event, $reason, $newDate, $newTime, $hasNewDate, $notifyEmail, $notifyInApp) {
            $oldValues = $event->only([
                'status',
                'date',
                'time',
                'postponement_reason',
                'postponed_at',
                'date_tba',
            ]);

            $payload = [
                'status' => Event::STATUS_POSTPONED,
                'postponement_reason' => $reason,
                'postponed_at' => now(),
                'date_tba' => ! $hasNewDate,
            ];

            if ($hasNewDate) {
                $payload['date'] = $newDate;
                if (filled($newTime)) {
                    $payload['time'] = $newTime;
                }
            }

            $event->update($payload);
            $event->refresh();

            $this->auditLogService->logEventPostponed(
                $event,
                $oldValues,
                $event->only([
                    'status',
                    'date',
                    'time',
                    'postponement_reason',
                    'postponed_at',
                    'date_tba',
                ]),
            );

            if (! $notifyEmail && ! $notifyInApp) {
                return;
            }

            $eventId = $event->id;

            DB::afterCommit(function () use ($eventId, $reason, $notifyEmail, $notifyInApp) {
                $event = Event::query()->find($eventId);

                if (! $event) {
                    return;
                }

                $notificationService = app(EventNotificationService::class);
                $recipients = $notificationService->interestedAttendees($event);

                foreach ($recipients as $user) {
                    if ($notifyEmail && filled($user->email)) {
                        Mail::to($user->email)->send(new EventPostponedMail($event, $user, $reason));
                    }

                    if ($notifyInApp) {
                        $user->notify(new EventPostponedNotification($event, $reason));
                    }
                }
            });
        });
    }

    /**
     * Set / update the announced schedule for a postponed event.
     * Status remains postponed.
     */
    public function setPostponedSchedule(
        Event $event,
        string $newDate,
        ?string $newTime = null,
        ?string $newPlace = null,
        bool $notify = true,
    ): void {
        if (! $event->isPostponed()) {
            throw new RuntimeException('Only postponed events can receive a postponed schedule update.');
        }

        DB::transaction(function () use ($event, $newDate, $newTime, $newPlace, $notify) {
            $oldValues = $event->only([
                'status',
                'date',
                'time',
                'place',
                'postponement_reason',
                'postponed_at',
                'date_tba',
            ]);

            $payload = [
                'date' => $newDate,
                'date_tba' => false,
                'status' => Event::STATUS_POSTPONED,
                'postponed_at' => now(),
            ];

            if (filled($newTime)) {
                $payload['time'] = $newTime;
            }

            if (filled($newPlace)) {
                $payload['place'] = $newPlace;
            }

            $event->update($payload);
            $event->refresh();

            $this->auditLogService->logEventRescheduled(
                $event,
                $oldValues,
                $event->only([
                    'status',
                    'date',
                    'time',
                    'place',
                    'postponement_reason',
                    'postponed_at',
                    'date_tba',
                ]),
            );

            if (! $notify) {
                return;
            }

            $eventId = $event->id;

            DB::afterCommit(function () use ($eventId) {
                $event = Event::query()->find($eventId);

                if (! $event) {
                    return;
                }

                $notificationService = app(EventNotificationService::class);
                $recipients = $notificationService->interestedAttendees($event);

                foreach ($recipients as $user) {
                    if (filled($user->email)) {
                        Mail::to($user->email)->send(new EventRescheduledMail($event, $user));
                    }

                    $user->notify(new EventRescheduledNotification($event));
                }
            });
        });
    }

    /**
     * Confirm place/date/time for an upcoming event that was published without a schedule.
     */
    public function confirmUpcomingSchedule(
        Event $event,
        string $newDate,
        ?string $newTime,
        string $newPlace,
        bool $notify = true,
    ): void {
        if ($event->status !== Event::STATUS_UPCOMING) {
            throw new RuntimeException('Only upcoming events can confirm an upcoming schedule.');
        }

        DB::transaction(function () use ($event, $newDate, $newTime, $newPlace, $notify) {
            $oldValues = $event->only(['status', 'date', 'time', 'place', 'date_tba']);

            $payload = [
                'date' => $newDate,
                'place' => $newPlace,
                'date_tba' => false,
                'status' => Event::STATUS_UPCOMING,
            ];

            if (filled($newTime)) {
                $payload['time'] = $newTime;
            }

            $event->update($payload);
            $event->refresh();

            $this->auditLogService->logEventRescheduled(
                $event,
                $oldValues,
                $event->only(['status', 'date', 'time', 'place', 'date_tba']),
            );

            if (! $notify) {
                return;
            }

            $eventId = $event->id;

            DB::afterCommit(function () use ($eventId) {
                $event = Event::query()->find($eventId);

                if (! $event) {
                    return;
                }

                $notificationService = app(EventNotificationService::class);
                $savers = $notificationService->getUsersWhoSavedEvent($event);
                $ticketHolders = $notificationService->getConfirmedTicketHolders($event);
                $ticketHolderIds = $ticketHolders->pluck('id')->all();

                foreach ($savers as $user) {
                    if (in_array($user->id, $ticketHolderIds, true)) {
                        continue;
                    }

                    if (filled($user->email)) {
                        Mail::to($user->email)->send(new EventScheduleAnnouncedMail($event, $user));
                    }

                    $user->notify(new EventScheduleAnnouncedNotification($event));
                }

                foreach ($ticketHolders as $user) {
                    if (filled($user->email)) {
                        Mail::to($user->email)->send(new EventScheduleAnnouncedMail($event, $user));
                    }

                    $user->notify(new EventScheduleAnnouncedNotification($event));
                }
            });
        });
    }

    /**
     * Promote a postponed event back to upcoming after a concrete schedule is set.
     */
    public function rescheduleToUpcoming(Event $event, bool $notify = true): void
    {
        if (! $event->isPostponed()) {
            return;
        }

        DB::transaction(function () use ($event, $notify) {
            $event->update([
                'status' => Event::STATUS_UPCOMING,
                'date_tba' => false,
            ]);

            $event->refresh();

            if (! $notify) {
                return;
            }

            $eventId = $event->id;

            DB::afterCommit(function () use ($eventId) {
                $event = Event::query()->find($eventId);

                if (! $event) {
                    return;
                }

                $recipients = app(EventNotificationService::class)->interestedAttendees($event);

                foreach ($recipients as $user) {
                    if (filled($user->email)) {
                        Mail::to($user->email)->send(new EventRescheduledMail($event, $user));
                    }

                    $user->notify(new EventRescheduledNotification($event));
                }
            });
        });
    }
}
