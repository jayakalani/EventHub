<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventCompletionService
{
    /**
     * Mark eligible events as completed once their event date has passed.
     */
    public function completePastEvents(): int
    {
        $today = Carbon::today()->toDateString();

        $events = Event::query()
            ->whereNotNull('date')
            ->whereDate('date', '<', $today)
            ->where('date_tba', false)
            ->whereNotIn('status', [
                Event::STATUS_COMPLETED,
                Event::STATUS_CANCELLED,
            ])
            ->get();

        $count = 0;

        foreach ($events as $event) {
            if ($this->completeIfPast($event)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Ensure a single event is marked completed when its date has passed.
     */
    public function completeIfPast(Event $event): bool
    {
        if ($event->isCompleted() || $event->isCancelled() || $event->hasDateYetToBeScheduled() || ! $event->hasPassed()) {
            return false;
        }

        return DB::transaction(function () use ($event) {
            $event->refresh();

            if ($event->isCompleted() || $event->isCancelled() || $event->hasDateYetToBeScheduled() || ! $event->hasPassed()) {
                return false;
            }

            $event->update(['status' => Event::STATUS_COMPLETED]);

            $eventId = $event->id;

            DB::afterCommit(function () use ($eventId) {
                $completed = Event::query()->find($eventId);

                if ($completed) {
                    app(EventNotificationService::class)->notifyEventCompleted($completed);
                }
            });

            return true;
        });
    }
}
