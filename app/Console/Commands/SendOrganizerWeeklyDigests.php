<?php

namespace App\Console\Commands;

use App\Mail\OrganizerWeeklyDigestMail;
use App\Models\Event;
use App\Models\User;
use App\Models\UserRole;
use App\Services\OrganizerNotificationService;
use App\Services\OrganizerReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOrganizerWeeklyDigests extends Command
{
    protected $signature = 'organizer:send-weekly-digests';

    protected $description = 'Email organizers a weekly performance digest (revenue, tickets, top/bottom event, attendance)';

    public function handle(
        OrganizerReportService $reportService,
        OrganizerNotificationService $notificationService,
    ): int {
        $organizerIds = Event::query()
            ->whereNotNull('created_by')
            ->distinct()
            ->pluck('created_by');

        $organizers = User::query()
            ->whereIn('id', $organizerIds)
            ->whereHas('userRole', fn ($query) => $query->where('name_en', UserRole::ORGANIZER))
            ->get();

        $sent = 0;

        foreach ($organizers as $organizer) {
            $digest = $reportService->getWeeklyDigestPayload((int) $organizer->id);

            if (
                $digest['ticketsSold'] <= 0
                && $digest['netRevenue'] <= 0
                && $digest['checkedIn'] <= 0
            ) {
                continue;
            }

            if ($notificationService->notifyWeeklyDigest($organizer, $digest)) {
                Mail::to($organizer)->queue(new OrganizerWeeklyDigestMail($organizer, $digest));
                $sent++;
            }
        }

        $this->info(sprintf('Sent %d organizer weekly digest(s).', $sent));

        return self::SUCCESS;
    }
}
