<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\RefundRequest;
use App\Models\ticketBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CroDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $openInquiryCount = Inquiry::where('status', SupportTicketStatusEnum::Open)->count();
        $openComplaintCount = Complaint::where('status', SupportTicketStatusEnum::Open)->count();
        $inProgressComplaintCount = Complaint::where('status', SupportTicketStatusEnum::InProgress)->count();
        $activeComplaintCount = $openComplaintCount + $inProgressComplaintCount;

        $resolvedToday = Inquiry::query()
            ->whereIn('status', [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed])
            ->whereDate('updated_at', today())
            ->count()
            + Complaint::query()
                ->whereIn('status', [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed])
                ->whereDate('updated_at', today())
                ->count();

        $pendingRefundCount = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Pending)
            ->count();

        $newInquiriesToday = Inquiry::whereDate('created_at', today())->count();
        $urgentComplaintCount = $this->urgentOpenComplaintCount();
        $eventsToday = Event::query()
            ->visibleToAttendees()
            ->whereDate('date', today())
            ->where('status', '!=', Event::STATUS_CANCELLED)
            ->count();

        $complaintByStatus = $this->complaintStatusBreakdown();

        return [
            'kpis' => [
                'openInquiries' => $openInquiryCount,
                'activeComplaints' => $activeComplaintCount,
                'resolvedToday' => $resolvedToday,
                'avgResponseMinutes' => $this->averageResponseMinutes(),
                'avgResponseLabel' => $this->formatResponseTime($this->averageResponseMinutes()),
            ],
            'todayTasks' => [
                'newInquiries' => $newInquiriesToday,
                'refundRequests' => $pendingRefundCount,
                'urgentComplaints' => $urgentComplaintCount,
                'eventsToday' => $eventsToday,
            ],
            'charts' => [
                'inquiryTrend' => $this->weeklyInquiryTrend(),
                'complaintStatus' => $complaintByStatus,
                'supportCategories' => $this->supportCategories(),
            ],
            'recentInquiries' => $this->recentInquiries(6),
            'highPriority' => $this->highPriorityCases(6),
            'pendingRefunds' => $this->pendingRefundRequests(5),
            'satisfaction' => $this->customerSatisfaction(),
            'recentActivity' => $this->recentActivity(8),
            'eventsToday' => $this->eventsSupportOverview(),
            'counts' => [
                'pendingRefunds' => $pendingRefundCount,
                'openInquiries' => $openInquiryCount,
                'openComplaints' => $openComplaintCount,
                'inProgress' => Inquiry::where('status', SupportTicketStatusEnum::InProgress)->count()
                    + Complaint::where('status', SupportTicketStatusEnum::InProgress)->count(),
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, counts: list<int>}
     */
    private function weeklyInquiryTrend(): array
    {
        $days = collect(range(6, 0))->map(fn (int $i) => today()->subDays($i));

        $counts = Inquiry::query()
            ->where('created_at', '>=', today()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        return [
            'labels' => $days->map(fn (Carbon $day) => $day->format('D'))->values()->all(),
            'counts' => $days->map(function (Carbon $day) use ($counts) {
                return (int) ($counts[$day->toDateString()] ?? 0);
            })->values()->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, counts: list<int>, percents: list<float>}
     */
    private function complaintStatusBreakdown(): array
    {
        $resolved = Complaint::query()
            ->whereIn('status', [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed])
            ->count();
        $pending = Complaint::where('status', SupportTicketStatusEnum::Open)->count();
        $inProgress = Complaint::where('status', SupportTicketStatusEnum::InProgress)->count();

        $counts = [$resolved, $pending, $inProgress];
        $total = max(1, array_sum($counts));

        return [
            'labels' => ['Resolved', 'Pending', 'In Progress'],
            'counts' => $counts,
            'percents' => collect($counts)
                ->map(fn (int $count) => round(($count / $total) * 100, 1))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{customer: string, subject: string, event: string, status: string, statusClass: string, time: string}>
     */
    private function recentInquiries(int $limit = 6): array
    {
        return Inquiry::with(['user', 'event'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Inquiry $inquiry) => [
                'customer' => $inquiry->user?->full_name ?? 'Unknown',
                'subject' => $inquiry->subject,
                'event' => $inquiry->event?->name ?? '—',
                'status' => $inquiry->status === SupportTicketStatusEnum::Open
                    ? 'Pending'
                    : $inquiry->status->label(),
                'statusClass' => $inquiry->status->badgeClass(),
                'time' => $inquiry->created_at?->diffForHumans() ?? '—',
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, meta: string, href: string, type: string}>
     */
    private function highPriorityCases(int $limit = 6): array
    {
        $cases = collect();

        RefundRequest::query()
            ->with(['user', 'ticketBooking.event'])
            ->where('status', RefundRequestStatusEnum::Pending)
            ->latest()
            ->limit($limit)
            ->get()
            ->each(function (RefundRequest $refund) use ($cases) {
                $cases->push([
                    'title' => 'Refund Pending',
                    'meta' => trim(($refund->user?->full_name ?? 'Customer').' · '.($refund->ticketBooking?->event?->name ?? 'Event')),
                    'href' => route('cro.refund-requests.index'),
                    'type' => 'refund',
                    'sort' => $refund->created_at?->timestamp ?? 0,
                ]);
            });

        $openStatuses = [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress];

        Complaint::query()
            ->with('user')
            ->whereIn('status', $openStatuses)
            ->latest()
            ->limit($limit * 2)
            ->get()
            ->filter(fn (Complaint $complaint) => $this->isUrgentSubject($complaint->subject))
            ->each(function (Complaint $complaint) use ($cases) {
                $cases->push([
                    'title' => $this->priorityTitle($complaint->subject),
                    'meta' => ($complaint->user?->full_name ?? 'Customer').' · Complaint',
                    'href' => route('cro.complaints.index'),
                    'type' => 'complaint',
                    'sort' => $complaint->created_at?->timestamp ?? 0,
                ]);
            });

        Inquiry::query()
            ->with(['user', 'event'])
            ->whereIn('status', $openStatuses)
            ->latest()
            ->limit($limit * 2)
            ->get()
            ->filter(fn (Inquiry $inquiry) => $this->isUrgentSubject($inquiry->subject))
            ->each(function (Inquiry $inquiry) use ($cases) {
                $cases->push([
                    'title' => $this->priorityTitle($inquiry->subject),
                    'meta' => trim(($inquiry->user?->full_name ?? 'Customer').' · '.($inquiry->event?->name ?? 'Inquiry')),
                    'href' => route('cro.inquiries.index'),
                    'type' => 'inquiry',
                    'sort' => $inquiry->created_at?->timestamp ?? 0,
                ]);
            });

        return $cases
            ->sortByDesc('sort')
            ->take($limit)
            ->values()
            ->map(fn (array $case) => collect($case)->except('sort')->all())
            ->all();
    }

    private function urgentOpenComplaintCount(): int
    {
        return Complaint::query()
            ->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])
            ->where(function ($query) {
                foreach (['payment', 'refund', 'duplicate', 'cancel', 'urgent', 'failed', 'fraud'] as $term) {
                    $query->orWhere('subject', 'like', "%{$term}%");
                }
            })
            ->count();
    }

    private function isUrgentSubject(string $subject): bool
    {
        $subject = strtolower($subject);

        return str_contains($subject, 'payment')
            || str_contains($subject, 'refund')
            || str_contains($subject, 'duplicate')
            || str_contains($subject, 'cancel')
            || str_contains($subject, 'urgent')
            || str_contains($subject, 'failed')
            || str_contains($subject, 'fraud');
    }

    private function priorityTitle(string $subject): string
    {
        $lower = strtolower($subject);

        if (str_contains($lower, 'payment') && str_contains($lower, 'fail')) {
            return 'Payment Failed';
        }
        if (str_contains($lower, 'refund')) {
            return 'Refund Pending';
        }
        if (str_contains($lower, 'duplicate')) {
            return 'Duplicate Ticket';
        }
        if (str_contains($lower, 'cancel')) {
            return 'Event Cancelled';
        }

        return $subject;
    }

    /**
     * @return list<array{customer: string, event: string, amount: string, status: string, statusClass: string}>
     */
    private function pendingRefundRequests(int $limit = 5): array
    {
        return RefundRequest::query()
            ->with(['user', 'ticketBooking.event'])
            ->where('status', RefundRequestStatusEnum::Pending)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (RefundRequest $refund) => [
                'customer' => $refund->user?->full_name ?? 'Unknown',
                'event' => $refund->ticketBooking?->event?->name ?? '—',
                'amount' => 'LKR '.number_format((float) $refund->refund_amount, 0),
                'status' => 'Waiting Approval',
                'statusClass' => 'bg-amber-100 text-amber-700',
            ])
            ->all();
    }

    /**
     * @return array{average: float|null, reviewCount: int, happyPercent: float, label: string}
     */
    private function customerSatisfaction(): array
    {
        $resolvedStatuses = [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed];

        $inquiryTotal = Inquiry::count();
        $complaintTotal = Complaint::count();
        $total = $inquiryTotal + $complaintTotal;

        $resolved = Inquiry::whereIn('status', $resolvedStatuses)->count()
            + Complaint::whereIn('status', $resolvedStatuses)->count();

        $happyPercent = $total > 0 ? round(($resolved / $total) * 100, 1) : 0.0;
        $average = $total > 0 ? round(($happyPercent / 100) * 5, 1) : null;

        return [
            'average' => $average,
            'reviewCount' => $resolved,
            'happyPercent' => $happyPercent,
            'label' => $resolved > 0
                ? 'Based on '.$resolved.' resolved cases'
                : 'No resolved cases yet',
        ];
    }

    /**
     * @return list<array{time: string, title: string, meta: string, icon: string, color: string}>
     */
    private function recentActivity(int $limit = 8): array
    {
        $activities = collect();

        AuditLog::query()
            ->with('user')
            ->where('action', 'like', 'CRO%')
            ->latest()
            ->limit($limit)
            ->get()
            ->each(function (AuditLog $log) use ($activities) {
                $activities->push([
                    'time' => $this->formatActivityTime($log->created_at),
                    'title' => $this->formatSupportAuditTitle($log),
                    'meta' => $log->user?->full_name ?? 'CRO',
                    'icon' => str_contains(strtolower($log->action), 'reply') ? 'bi-chat-dots' : 'bi-arrow-repeat',
                    'color' => 'indigo',
                    'sort' => $log->created_at?->timestamp ?? 0,
                ]);
            });

        Complaint::query()
            ->whereIn('status', [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed])
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->each(function (Complaint $complaint) use ($activities) {
                $activities->push([
                    'time' => $this->formatActivityTime($complaint->updated_at),
                    'title' => 'Complaint Resolved',
                    'meta' => $complaint->subject,
                    'icon' => 'bi-check2-circle',
                    'color' => 'emerald',
                    'sort' => $complaint->updated_at?->timestamp ?? 0,
                ]);
            });

        Inquiry::query()
            ->whereNotNull('assigned_to')
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->each(function (Inquiry $inquiry) use ($activities) {
                $activities->push([
                    'time' => $this->formatActivityTime($inquiry->updated_at),
                    'title' => 'Inquiry Assigned',
                    'meta' => $inquiry->subject,
                    'icon' => 'bi-person-check',
                    'color' => 'blue',
                    'sort' => $inquiry->updated_at?->timestamp ?? 0,
                ]);
            });

        RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Approved)
            ->latest('reviewed_at')
            ->limit(4)
            ->get()
            ->each(function (RefundRequest $refund) use ($activities) {
                $at = $refund->reviewed_at ?? $refund->updated_at;
                $activities->push([
                    'time' => $this->formatActivityTime($at),
                    'title' => 'Refund Approved',
                    'meta' => 'LKR '.number_format((float) $refund->refund_amount, 0),
                    'icon' => 'bi-cash-coin',
                    'color' => 'amber',
                    'sort' => $at?->timestamp ?? 0,
                ]);
            });

        return $activities
            ->sortByDesc('sort')
            ->unique(fn (array $item) => $item['title'].'|'.$item['meta'].'|'.$item['time'])
            ->take($limit)
            ->values()
            ->map(fn (array $item) => collect($item)->except('sort')->all())
            ->all();
    }

    /**
     * @return array{labels: list<string>, counts: list<int>}
     */
    private function supportCategories(): array
    {
        $categories = [
            'Payment Issues' => 0,
            'Ticket Issues' => 0,
            'Refund Requests' => 0,
            'Event Information' => 0,
            'Account Issues' => 0,
        ];

        Inquiry::query()
            ->get(['subject'])
            ->each(function (Inquiry $inquiry) use (&$categories) {
                $label = $this->classifyInquiryCategory($inquiry->subject);
                $categories[$label] = ($categories[$label] ?? 0) + 1;
            });

        $categories['Refund Requests'] += RefundRequest::count();

        return [
            'labels' => array_keys($categories),
            'counts' => array_values($categories),
        ];
    }

    /**
     * @return list<array{name: string, attendees: int, openInquiries: int, pendingRefunds: int}>
     */
    private function eventsSupportOverview(): array
    {
        $events = Event::query()
            ->visibleToAttendees()
            ->whereDate('date', today())
            ->where('status', '!=', Event::STATUS_CANCELLED)
            ->orderBy('time')
            ->get(['id', 'name']);

        if ($events->isEmpty()) {
            return [];
        }

        $eventIds = $events->pluck('id');

        $attendees = ticketBooking::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', BookingStatusEnum::Confirmed)
            ->selectRaw('event_id, COUNT(*) as count')
            ->groupBy('event_id')
            ->pluck('count', 'event_id');

        $openInquiries = Inquiry::query()
            ->whereIn('event_id', $eventIds)
            ->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])
            ->selectRaw('event_id, COUNT(*) as count')
            ->groupBy('event_id')
            ->pluck('count', 'event_id');

        $pendingRefunds = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Pending)
            ->whereHas('ticketBooking', fn ($q) => $q->whereIn('event_id', $eventIds))
            ->with('ticketBooking:id,event_id')
            ->get()
            ->countBy(fn (RefundRequest $refund) => $refund->ticketBooking?->event_id);

        return $events->map(fn (Event $event) => [
            'name' => $event->name,
            'attendees' => (int) ($attendees[$event->id] ?? 0),
            'openInquiries' => (int) ($openInquiries[$event->id] ?? 0),
            'pendingRefunds' => (int) ($pendingRefunds[$event->id] ?? 0),
        ])->values()->all();
    }

    private function classifyInquiryCategory(string $subject): string
    {
        $subject = strtolower($subject);

        if (str_contains($subject, 'refund') || str_contains($subject, 'money back')) {
            return 'Refund Requests';
        }

        if (str_contains($subject, 'payment') || str_contains($subject, 'billing') || str_contains($subject, 'charge') || str_contains($subject, 'failed')) {
            return 'Payment Issues';
        }

        if (str_contains($subject, 'ticket') || str_contains($subject, 'seat') || str_contains($subject, 'booking') || str_contains($subject, 'duplicate')) {
            return 'Ticket Issues';
        }

        if (str_contains($subject, 'account') || str_contains($subject, 'login') || str_contains($subject, 'password') || str_contains($subject, 'profile')) {
            return 'Account Issues';
        }

        return 'Event Information';
    }

    private function formatSupportAuditTitle(AuditLog $log): string
    {
        $action = strtolower($log->action);
        $isInquiry = str_contains($action, 'inquiry');
        $entity = $isInquiry ? 'Inquiry' : 'Complaint';

        if (str_contains($action, 'reply')) {
            return "{$entity} Reply Sent";
        }

        $newValues = is_array($log->new_values)
            ? $log->new_values
            : (json_decode((string) $log->new_values, true) ?: []);

        $status = $newValues['status'] ?? null;
        if ($status === SupportTicketStatusEnum::Resolved->value || $status === SupportTicketStatusEnum::Closed->value) {
            return "{$entity} Resolved";
        }
        if ($status === SupportTicketStatusEnum::InProgress->value) {
            return "{$entity} In Progress";
        }

        return "{$entity} Updated";
    }

    private function formatActivityTime(?Carbon $time): string
    {
        if (! $time) {
            return '—';
        }

        if ($time->isToday()) {
            return $time->format('g:i A');
        }

        if ($time->isYesterday()) {
            return 'Yesterday';
        }

        return $time->format('M j');
    }

    private function averageResponseMinutes(): ?float
    {
        $firstResponses = InquiryResponse::query()
            ->select('inquiry_id', DB::raw('MIN(created_at) as first_response_at'))
            ->groupBy('inquiry_id');

        $avg = DB::table('inquiries as i')
            ->joinSub($firstResponses, 'fr', 'fr.inquiry_id', '=', 'i.id')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, i.created_at, fr.first_response_at)'));

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    private function formatResponseTime(?float $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        if ($minutes < 60) {
            return round($minutes).'m';
        }

        $hours = floor($minutes / 60);
        $mins = (int) round($minutes % 60);

        return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
    }
}
