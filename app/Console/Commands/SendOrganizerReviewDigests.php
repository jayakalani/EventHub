<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\OrganizerNotificationService;
use Illuminate\Console\Command;

class SendOrganizerReviewDigests extends Command
{
    protected $signature = 'events:send-organizer-review-digests';

    protected $description = 'Notify organizers of new post-event reviews (N new, avg X)';

    public function handle(OrganizerNotificationService $organizerNotificationService): int
    {
        $events = Event::query()
            ->where('status', Event::STATUS_COMPLETED)
            ->whereNotNull('date')
            ->where(function ($query) {
                $query->where('date_tba', false)->orWhereNull('date_tba');
            })
            ->whereDate('date', '>=', now()->subDays(14)->toDateString())
            ->whereDate('date', '<=', now()->toDateString())
            ->with('organizer.userRole')
            ->get();

        $sent = 0;

        foreach ($events as $event) {
            if ($organizerNotificationService->notifyReviewDigest($event)) {
                $sent++;
            }
        }

        $this->info(sprintf('Sent %d organizer review digest(s).', $sent));

        return self::SUCCESS;
    }
}
