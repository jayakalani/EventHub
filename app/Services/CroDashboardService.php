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
use App\Models\Rating;
use App\Models\RefundRequest;
use App\Models\ticketBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CroDashboardService
{
    /**
     * @param  array{
     *     event?: int|null,
     *     from?: string|null,
     *     to?: string|null
     * }  $filters
     * @return array<string, mixed>
     */
    public function getDashboardData(array $filters = []): array
    {
        $eventId = isset($filters['event']) ? (int) $filters['event'] : null;
        $from = $this->parseDate($filters['from'] ?? null)?->startOfDay();
        $to = $this->parseDate($filters['to'] ?? null)?->endOfDay();

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if (! $from && ! $to) {
            $from = now()->subDays(29)->startOfDay();
            $to = now()->endOfDay();
        } elseif ($from && ! $to) {
            $to = now()->endOfDay();
        } elseif (! $from && $to) {
            $from = $to->copy()->subDays(29)->startOfDay();
        }

        $eventFilter = $this->eventFilterOptions($eventId);
        $selectedEventId = $eventFilter['selectedEventId'];

        $openInquiryCount = $this->inquiryQuery($selectedEventId)
            ->where('status', SupportTicketStatusEnum::Open)
            ->count();
        $openComplaintCount = $this->complaintQuery($selectedEventId)
            ->where('status', SupportTicketStatusEnum::Open)
            ->count();
        $inProgressComplaintCount = $this->complaintQuery($selectedEventId)
            ->where('status', SupportTicketStatusEnum::InProgress)
            ->count();
        $activeComplaintCount = $openComplaintCount + $inProgressComplaintCount;

        $resolvedStatuses = [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed];
        $resolvedToday = $this->inquiryQuery($selectedEventId)
            ->whereIn('status', $resolvedStatuses)
            ->whereDate('updated_at', today())
            ->count()
            + $this->complaintQuery($selectedEventId)
                ->whereIn('status', $resolvedStatuses)
                ->whereDate('updated_at', today())
                ->count();

        $pendingRefundCount = $this->refundQuery($selectedEventId)
            ->where('status', RefundRequestStatusEnum::Pending)
            ->count();

        $newInquiriesToday = $this->inquiryQuery($selectedEventId)
            ->whereDate('created_at', today())
            ->count();
        $urgentComplaintCount = $this->urgentOpenComplaintCount($selectedEventId);
        $eventsToday = Event::query()
            ->visibleToAttendees()
            ->whereDate('date', today())
            ->where('status', '!=', Event::STATUS_CANCELLED)
            ->when($selectedEventId, fn (Builder $q) => $q->where('id', $selectedEventId))
            ->count();

        $complaintByStatus = $this->complaintStatusBreakdown($selectedEventId);

        return [
            'filters' => [
                'event' => $selectedEventId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'eventFilter' => $eventFilter,
            'kpis' => [
                'openInquiries' => $openInquiryCount,
                'activeComplaints' => $activeComplaintCount,
                'resolvedToday' => $resolvedToday,
                'avgResponseMinutes' => $this->averageResponseMinutes($selectedEventId),
                'avgResponseLabel' => $this->formatResponseTime($this->averageResponseMinutes($selectedEventId)),
            ],
            'todayTasks' => [
                'newInquiries' => $newInquiriesToday,
                'refundRequests' => $pendingRefundCount,
                'urgentComplaints' => $urgentComplaintCount,
                'eventsToday' => $eventsToday,
            ],
            'charts' => [
                'defaultPeriod' => 'week',
                'periods' => [
                    'week' => $this->supportTrend('week', $selectedEventId, $from, $to),
                    'month' => $this->supportTrend('month', $selectedEventId, $from, $to),
                ],
                'complaintStatus' => $complaintByStatus,
                'supportCategories' => $this->supportCategories($selectedEventId, $from, $to),
                'satisfactionDistribution' => $this->satisfactionDistribution($selectedEventId),
            ],
            'recentInquiries' => $this->recentInquiries(6, $selectedEventId, $from, $to),
            'highPriority' => $this->highPriorityCases(6, $selectedEventId),
            'pendingRefunds' => $this->pendingRefundRequests(5, $selectedEventId),
            'satisfaction' => $this->customerSatisfaction($selectedEventId),
            'feedbackThemes' => $this->topFeedbackThemes($selectedEventId, $from, $to, 5),
            'recentActivity' => $this->recentActivity(8, $selectedEventId),
            'eventsToday' => $this->eventsSupportOverview($selectedEventId),
            'miniCalendar' => app(DashboardCalendarWidgetService::class)->forCro(),
            'counts' => [
                'pendingRefunds' => $pendingRefundCount,
                'openInquiries' => $openInquiryCount,
                'openComplaints' => $openComplaintCount,
                'inProgress' => $this->inquiryQuery($selectedEventId)
                    ->where('status', SupportTicketStatusEnum::InProgress)
                    ->count()
                    + $this->complaintQuery($selectedEventId)
                        ->where('status', SupportTicketStatusEnum::InProgress)
                        ->count(),
            ],
        ];
    }

    /**
     * @return array{selectedEventId: int|null, selectedEventName: string|null, events: list<array{id: int, name: string}>}
     */
    private function eventFilterOptions(?int $eventId): array
    {
        $events = Event::query()
            ->where(function (Builder $query) {
                $query->whereIn('id', Inquiry::query()->whereNotNull('event_id')->select('event_id'))
                    ->orWhereIn('id', ticketBooking::query()
                        ->whereIn('id', RefundRequest::query()->select('ticket_booking_id'))
                        ->select('event_id'))
                    ->orWhere(function (Builder $upcoming) {
                        $upcoming->visibleToAttendees()
                            ->whereDate('date', '>=', today()->subMonths(1))
                            ->whereDate('date', '<=', today()->addMonths(2));
                    });
            })
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'name', 'date'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date ? Carbon::parse($event->date)->format('M j, Y') : null,
            ])
            ->values()
            ->all();

        $selected = collect($events)->firstWhere('id', $eventId);

        if ($eventId && ! $selected) {
            $fallback = Event::query()->find($eventId);
            if ($fallback) {
                $selected = [
                    'id' => $fallback->id,
                    'name' => $fallback->name,
                    'date' => $fallback->date ? Carbon::parse($fallback->date)->format('M j, Y') : null,
                ];
                array_unshift($events, $selected);
            }
        }

        return [
            'selectedEventId' => $selected['id'] ?? null,
            'selectedEventName' => $selected['name'] ?? null,
            'events' => $events,
        ];
    }

    private function inquiryQuery(?int $eventId): Builder
    {
        return Inquiry::query()->when($eventId, fn (Builder $q) => $q->where('event_id', $eventId));
    }

    private function complaintQuery(?int $eventId): Builder
    {
        return Complaint::query()->when($eventId, function (Builder $q) use ($eventId) {
            $q->whereIn('user_id', ticketBooking::query()
                ->where('event_id', $eventId)
                ->select('user_id'));
        });
    }

    private function refundQuery(?int $eventId): Builder
    {
        return RefundRequest::query()->when($eventId, function (Builder $q) use ($eventId) {
            $q->whereHas('ticketBooking', fn (Builder $booking) => $booking->where('event_id', $eventId));
        });
    }

    /**
     * @return array{label: string, labels: list<string>, inquiries: list<int>, complaints: list<int>, refunds: list<int>}
     */
    private function supportTrend(string $period, ?int $eventId, Carbon $from, Carbon $to): array
    {
        if ($period === 'month') {
            $start = $from->copy()->startOfMonth()->max(now()->subMonths(5)->startOfMonth());
            $end = $to->copy()->endOfMonth()->min(now()->endOfMonth());
            if ($start->gt($end)) {
                $start = now()->subMonths(5)->startOfMonth();
                $end = now()->endOfMonth();
            }

            $months = collect();
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $months->push($cursor->copy());
                $cursor->addMonth();
            }

            return [
                'label' => 'Monthly',
                'labels' => $months->map(fn (Carbon $m) => $m->format('M Y'))->all(),
                'inquiries' => $months->map(fn (Carbon $m) => $this->inquiryQuery($eventId)
                    ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                    ->count())->all(),
                'complaints' => $months->map(fn (Carbon $m) => $this->complaintQuery($eventId)
                    ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                    ->count())->all(),
                'refunds' => $months->map(fn (Carbon $m) => $this->refundQuery($eventId)
                    ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                    ->count())->all(),
            ];
        }

        $start = $from->copy()->startOfDay()->max(now()->subDays(6)->startOfDay());
        $end = $to->copy()->endOfDay()->min(now()->endOfDay());
        if ($start->gt($end)) {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();
        }

        // Prefer a full week window when the filter still covers it.
        if ($from->lte(now()->subDays(6)->startOfDay()) && $to->gte(now()->startOfDay())) {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();
        }

        $days = collect();
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $days->push($cursor->copy());
            $cursor->addDay();
            if ($days->count() > 31) {
                break;
            }
        }

        return [
            'label' => 'Weekly',
            'labels' => $days->map(fn (Carbon $d) => $d->format('D j'))->all(),
            'inquiries' => $days->map(fn (Carbon $d) => $this->inquiryQuery($eventId)
                ->whereDate('created_at', $d->toDateString())
                ->count())->all(),
            'complaints' => $days->map(fn (Carbon $d) => $this->complaintQuery($eventId)
                ->whereDate('created_at', $d->toDateString())
                ->count())->all(),
            'refunds' => $days->map(fn (Carbon $d) => $this->refundQuery($eventId)
                ->whereDate('created_at', $d->toDateString())
                ->count())->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, counts: list<int>, percents: list<float>}
     */
    private function complaintStatusBreakdown(?int $eventId): array
    {
        $resolved = $this->complaintQuery($eventId)
            ->whereIn('status', [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed])
            ->count();
        $pending = $this->complaintQuery($eventId)->where('status', SupportTicketStatusEnum::Open)->count();
        $inProgress = $this->complaintQuery($eventId)->where('status', SupportTicketStatusEnum::InProgress)->count();

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
    private function recentInquiries(int $limit, ?int $eventId, Carbon $from, Carbon $to): array
    {
        return $this->inquiryQuery($eventId)
            ->with(['user', 'event'])
            ->whereBetween('created_at', [$from, $to])
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
    private function highPriorityCases(int $limit, ?int $eventId): array
    {
        $cases = collect();

        $this->refundQuery($eventId)
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

        $this->complaintQuery($eventId)
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

        $this->inquiryQuery($eventId)
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

    private function urgentOpenComplaintCount(?int $eventId): int
    {
        return $this->complaintQuery($eventId)
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
    private function pendingRefundRequests(int $limit, ?int $eventId): array
    {
        return $this->refundQuery($eventId)
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
     * @return array{
     *     average: float|null,
     *     reviewCount: int,
     *     happyPercent: float,
     *     label: string,
     *     source: string
     * }
     */
    private function customerSatisfaction(?int $eventId): array
    {
        $ratingsQuery = Rating::query()
            ->when($eventId, fn (Builder $q) => $q->where('event_id', $eventId));

        $reviewCount = (clone $ratingsQuery)->count();

        if ($reviewCount > 0) {
            $average = round((float) (clone $ratingsQuery)->avg('score'), 1);
            $happy = (clone $ratingsQuery)->where('score', '>=', 4)->count();

            return [
                'average' => $average,
                'reviewCount' => $reviewCount,
                'happyPercent' => round(($happy / $reviewCount) * 100, 1),
                'label' => 'Based on '.$reviewCount.' event rating'.($reviewCount === 1 ? '' : 's'),
                'source' => 'ratings',
            ];
        }

        $resolvedStatuses = [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed];
        $inquiryTotal = $this->inquiryQuery($eventId)->count();
        $complaintTotal = $this->complaintQuery($eventId)->count();
        $total = $inquiryTotal + $complaintTotal;

        $resolved = $this->inquiryQuery($eventId)->whereIn('status', $resolvedStatuses)->count()
            + $this->complaintQuery($eventId)->whereIn('status', $resolvedStatuses)->count();

        $happyPercent = $total > 0 ? round(($resolved / $total) * 100, 1) : 0.0;
        $average = $total > 0 ? round(($happyPercent / 100) * 5, 1) : null;

        return [
            'average' => $average,
            'reviewCount' => $resolved,
            'happyPercent' => $happyPercent,
            'label' => $resolved > 0
                ? 'Estimated from '.$resolved.' resolved support cases'
                : 'No ratings or resolved cases yet',
            'source' => 'support',
        ];
    }

    /**
     * @return array{labels: list<string>, counts: list<int>, percents: list<float>}
     */
    private function satisfactionDistribution(?int $eventId): array
    {
        $ratings = Rating::query()
            ->when($eventId, fn (Builder $q) => $q->where('event_id', $eventId))
            ->selectRaw('score, COUNT(*) as count')
            ->groupBy('score')
            ->pluck('count', 'score');

        $labels = ['5 stars', '4 stars', '3 stars', '2 stars', '1 star'];
        $scores = [5, 4, 3, 2, 1];
        $counts = collect($scores)->map(fn (int $score) => (int) ($ratings[$score] ?? 0))->all();
        $total = max(1, array_sum($counts));

        return [
            'labels' => $labels,
            'counts' => $counts,
            'percents' => collect($counts)
                ->map(fn (int $count) => round(($count / $total) * 100, 1))
                ->values()
                ->all(),
            'total' => array_sum($counts),
        ];
    }

    /**
     * @return list<array{label: string, count: int, percent: float}>
     */
    private function topFeedbackThemes(?int $eventId, Carbon $from, Carbon $to, int $limit = 5): array
    {
        $categories = $this->supportCategories($eventId, $from, $to);
        $total = max(1, array_sum($categories['counts']));

        return collect($categories['labels'])
            ->map(fn (string $label, int $index) => [
                'label' => $label,
                'count' => (int) ($categories['counts'][$index] ?? 0),
                'percent' => round((((int) ($categories['counts'][$index] ?? 0)) / $total) * 100, 1),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return list<array{time: string, title: string, meta: string, icon: string, color: string}>
     */
    private function recentActivity(int $limit, ?int $eventId): array
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

        $this->complaintQuery($eventId)
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

        $this->inquiryQuery($eventId)
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

        $this->refundQuery($eventId)
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
    private function supportCategories(?int $eventId, Carbon $from, Carbon $to): array
    {
        $categories = [
            'Payment Issues' => 0,
            'Ticket Issues' => 0,
            'Refund Delays' => 0,
            'Event Information' => 0,
            'Account Issues' => 0,
        ];

        $this->inquiryQuery($eventId)
            ->whereBetween('created_at', [$from, $to])
            ->get(['subject'])
            ->each(function (Inquiry $inquiry) use (&$categories) {
                $label = $this->classifyInquiryCategory($inquiry->subject);
                $categories[$label] = ($categories[$label] ?? 0) + 1;
            });

        $this->complaintQuery($eventId)
            ->whereBetween('created_at', [$from, $to])
            ->get(['subject'])
            ->each(function (Complaint $complaint) use (&$categories) {
                $label = $this->classifyInquiryCategory($complaint->subject);
                $categories[$label] = ($categories[$label] ?? 0) + 1;
            });

        $categories['Refund Delays'] += $this->refundQuery($eventId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return [
            'labels' => array_keys($categories),
            'counts' => array_values($categories),
        ];
    }

    /**
     * @return list<array{name: string, attendees: int, openInquiries: int, pendingRefunds: int}>
     */
    private function eventsSupportOverview(?int $eventId): array
    {
        $events = Event::query()
            ->visibleToAttendees()
            ->whereDate('date', today())
            ->where('status', '!=', Event::STATUS_CANCELLED)
            ->when($eventId, fn (Builder $q) => $q->where('id', $eventId))
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

        if (str_contains($subject, 'refund') || str_contains($subject, 'money back') || str_contains($subject, 'delay')) {
            return 'Refund Delays';
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

    private function averageResponseMinutes(?int $eventId): ?float
    {
        $firstResponses = InquiryResponse::query()
            ->select('inquiry_id', DB::raw('MIN(created_at) as first_response_at'))
            ->groupBy('inquiry_id');

        $query = DB::table('inquiries as i')
            ->joinSub($firstResponses, 'fr', 'fr.inquiry_id', '=', 'i.id')
            ->when($eventId, fn ($q) => $q->where('i.event_id', $eventId));

        $avg = $query->avg(DB::raw('TIMESTAMPDIFF(MINUTE, i.created_at, fr.first_response_at)'));

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

    private function parseDate(?string $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
