<?php

namespace App\Services;

use App\Enums\SupportTicketStatusEnum;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\Rating;
use App\Models\ticketBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CroReportService
{
    public function __construct(
        protected CroDashboardService $dashboardService,
    ) {}

    /**
     * @param  array{
     *     event?: int|null,
     *     cro?: int|null,
     *     range?: string|null,
     *     from?: string|null,
     *     to?: string|null
     * }  $filters
     * @return array<string, mixed>
     */
    public function getAllReports(array $filters = [], ?int $viewerCroId = null): array
    {
        $normalized = $this->normalizeFilters($filters);
        $eventId = $normalized['event'];
        // Always scope to the logged-in CRO's assigned events — never other CROs.
        $scopeCroId = $viewerCroId;

        if ($eventId && $scopeCroId && ! $this->croOwnsEvent($scopeCroId, $eventId)) {
            $eventId = null;
        }

        $from = $normalized['fromCarbon'];
        $to = $normalized['toCarbon'];
        $period = $normalized['period'];

        $inquiries = $this->getInquiryResolutionReport($eventId, $scopeCroId, $from, $to, $period);
        $complaints = $this->getComplaintStatisticsReport($eventId, $scopeCroId, $from, $to, $period);
        $satisfaction = $this->getSatisfactionReport($eventId, $from, $to, $period, $scopeCroId);
        $avgResponseLabel = $this->formatResponseTime(
            $this->averageResponseMinutes($eventId, $scopeCroId, $from, $to)
        );

        $eventOptions = $this->eventOptions($scopeCroId);

        $personalKpis = $scopeCroId
            ? $this->dashboardService->personalKpis($scopeCroId, $eventId, $from, $to)
            : null;

        return [
            'filters' => [
                'event' => $eventId,
                'cro' => null,
                'range' => $normalized['range'],
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'selectedEventName' => collect($eventOptions)->firstWhere('id', $eventId)['name'] ?? null,
                'selectedCroName' => null,
            ],
            'filterOptions' => [
                'events' => $eventOptions,
                'cros' => [],
            ],
            'summary' => [
                'resolved' => ($inquiries['resolvedOrClosed'] ?? 0) + ($complaints['resolved'] ?? 0) + ($complaints['closed'] ?? 0),
                'pending' => ($inquiries['active'] ?? 0) + ($complaints['open'] ?? 0) + ($complaints['inProgress'] ?? 0),
                'avgResponseLabel' => $avgResponseLabel,
                'resolutionRate' => $inquiries['resolutionRate'] ?? 0,
                'csatAverage' => $satisfaction['average'],
            ],
            'personalKpis' => $personalKpis,
            'inquiries' => $inquiries,
            'complaints' => $complaints,
            'satisfaction' => $satisfaction,
            'chartLabels' => $this->periodLabels($period, $from, $to),
        ];
    }

    /**
     * @return array{
     *     event: int|null,
     *     cro: int|null,
     *     range: string,
     *     fromCarbon: Carbon,
     *     toCarbon: Carbon,
     *     period: string
     * }
     */
    private function normalizeFilters(array $filters): array
    {
        $range = in_array($filters['range'] ?? null, ['week', 'month', 'custom'], true)
            ? $filters['range']
            : null;

        $from = $this->parseDate($filters['from'] ?? null);
        $to = $this->parseDate($filters['to'] ?? null);

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if (! $range) {
            if ($from || $to) {
                $range = 'custom';
            } else {
                $range = 'month';
            }
        }

        if ($range === 'week') {
            $from = now()->subDays(6)->startOfDay();
            $to = now()->endOfDay();
            $period = 'day';
        } elseif ($range === 'month') {
            $from = now()->subDays(29)->startOfDay();
            $to = now()->endOfDay();
            $period = 'day';
        } else {
            $from = ($from ?? now()->subDays(29))->startOfDay();
            $to = ($to ?? now())->endOfDay();
            $days = $from->diffInDays($to);
            $period = $days > 45 ? 'month' : 'day';
        }

        return [
            'event' => isset($filters['event']) ? (int) $filters['event'] : null,
            'cro' => isset($filters['cro']) ? (int) $filters['cro'] : null,
            'range' => $range,
            'fromCarbon' => $from,
            'toCarbon' => $to,
            'period' => $period,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getInquiryResolutionReport(?int $eventId, ?int $croId, Carbon $from, Carbon $to, string $period): array
    {
        $base = $this->inquiryQuery($eventId, $croId)->whereBetween('created_at', [$from, $to]);
        $total = (clone $base)->count();
        $byStatus = $this->countByStatus(clone $base);

        $resolvedOrClosed = $byStatus['resolved'] + $byStatus['closed'];
        $active = $byStatus['open'] + $byStatus['in_progress'];
        $trend = $this->inquiryResolutionTrend($eventId, $croId, $from, $to, $period);

        return [
            'total' => $total,
            'open' => $byStatus['open'],
            'inProgress' => $byStatus['in_progress'],
            'resolved' => $byStatus['resolved'],
            'closed' => $byStatus['closed'],
            'active' => $active,
            'resolvedOrClosed' => $resolvedOrClosed,
            'resolutionRate' => $total > 0 ? round(($resolvedOrClosed / $total) * 100, 1) : 0,
            'avgResponseMinutes' => $this->averageResponseMinutes($eventId, $croId, $from, $to),
            'avgResponseLabel' => $this->formatResponseTime(
                $this->averageResponseMinutes($eventId, $croId, $from, $to)
            ),
            'statusBreakdown' => $this->formatStatusBreakdown($byStatus),
            'resolutionTrend' => $trend,
            'responseTimeTrend' => $this->responseTimeTrend($eventId, $croId, $from, $to, $period),
            'submissionsTrend' => $trend['submitted'],
            'byEvent' => $this->inquiriesByEvent($eventId, $croId, $from, $to),
            'recentInquiries' => $this->inquiryQuery($eventId, $croId)
                ->with(['user', 'event', 'assignee'])
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Inquiry $inquiry) => [
                    'subject' => $inquiry->subject,
                    'user' => $inquiry->user?->full_name ?? 'Unknown',
                    'event' => $inquiry->event?->name ?? '—',
                    'status' => $inquiry->status->label(),
                    'statusClass' => $inquiry->status->badgeClass(),
                    'assignee' => $inquiry->assignee?->full_name ?? 'Unassigned',
                    'submitted' => $inquiry->created_at?->diffForHumans(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getComplaintStatisticsReport(?int $eventId, ?int $croId, Carbon $from, Carbon $to, string $period): array
    {
        $base = $this->complaintQuery($eventId, $croId)->whereBetween('created_at', [$from, $to]);
        $complaints = (clone $base)->get(['id', 'subject', 'status']);
        $total = $complaints->count();
        $byStatus = $this->countByStatus(clone $base);

        $byType = $complaints
            ->groupBy(fn (Complaint $complaint) => $this->classifyComplaintType($complaint->subject))
            ->map(fn (Collection $group, string $type) => [
                'label' => $type,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $categoryBreakdown = $this->complaintCategoryBreakdown($complaints);

        return [
            'total' => $total,
            'open' => $byStatus['open'],
            'inProgress' => $byStatus['in_progress'],
            'resolved' => $byStatus['resolved'],
            'closed' => $byStatus['closed'],
            'statusBreakdown' => $this->formatStatusBreakdown($byStatus),
            'typeBreakdown' => $byType,
            'categoryBreakdown' => $categoryBreakdown,
            'submissionsTrend' => $this->periodCounts(
                $this->complaintQuery($eventId, $croId),
                'created_at',
                $from,
                $to,
                $period
            ),
            'statusByType' => $this->complaintsByStatusAndType($complaints),
            'recentComplaints' => $this->complaintQuery($eventId, $croId)
                ->with(['user', 'assignee'])
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Complaint $complaint) => [
                    'subject' => $complaint->subject,
                    'user' => $complaint->user?->full_name ?? 'Unknown',
                    'type' => $this->classifyComplaintType($complaint->subject),
                    'status' => $complaint->status->label(),
                    'statusClass' => $complaint->status->badgeClass(),
                    'assignee' => $complaint->assignee?->full_name ?? 'Unassigned',
                    'submitted' => $complaint->created_at?->diffForHumans(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{
     *     average: float|null,
     *     reviewCount: int,
     *     distribution: array{labels: list<string>, counts: list<int>, percents: list<float>, total: int},
     *     trend: list<float|null>
     * }
     */
    private function getSatisfactionReport(?int $eventId, Carbon $from, Carbon $to, string $period, ?int $scopeCroId = null): array
    {
        $ratingsQuery = Rating::query()
            ->when($scopeCroId, fn (Builder $q) => $q->whereIn(
                'event_id',
                Event::query()->where('contact_person', $scopeCroId)->select('id')
            ))
            ->when($eventId, fn (Builder $q) => $q->where('event_id', $eventId))
            ->whereBetween('created_at', [$from, $to]);

        $reviewCount = (clone $ratingsQuery)->count();
        $average = $reviewCount > 0
            ? round((float) (clone $ratingsQuery)->avg('score'), 1)
            : null;

        $grouped = (clone $ratingsQuery)
            ->selectRaw('score, COUNT(*) as count')
            ->groupBy('score')
            ->pluck('count', 'score');

        $labels = ['5 stars', '4 stars', '3 stars', '2 stars', '1 star'];
        $scores = [5, 4, 3, 2, 1];
        $counts = collect($scores)->map(fn (int $score) => (int) ($grouped[$score] ?? 0))->all();
        $total = max(1, array_sum($counts));

        return [
            'average' => $average,
            'reviewCount' => $reviewCount,
            'distribution' => [
                'labels' => $labels,
                'counts' => $counts,
                'percents' => collect($counts)
                    ->map(fn (int $count) => round(($count / $total) * 100, 1))
                    ->values()
                    ->all(),
                'total' => array_sum($counts),
            ],
            'trend' => $this->csatTrend($eventId, $from, $to, $period, $scopeCroId),
        ];
    }

    private function inquiryQuery(?int $eventId, ?int $scopeCroId): Builder
    {
        return Inquiry::query()
            ->when($scopeCroId, fn (Builder $q) => $q->forCroQueue($scopeCroId, 'mine'))
            ->when($eventId, fn (Builder $q) => $q->where('event_id', $eventId));
    }

    private function complaintQuery(?int $eventId, ?int $scopeCroId): Builder
    {
        return Complaint::query()
            ->when($scopeCroId, fn (Builder $q) => $q->forCroQueue($scopeCroId, 'mine'))
            ->when($eventId, function (Builder $q) use ($eventId) {
                $q->whereIn('user_id', ticketBooking::query()
                    ->where('event_id', $eventId)
                    ->select('user_id'));
            });
    }

    /**
     * @return array{submitted: list<int>, resolved: list<int>, resolutionRate: list<float>}
     */
    private function inquiryResolutionTrend(?int $eventId, ?int $croId, Carbon $from, Carbon $to, string $period): array
    {
        $keys = $this->periodKeys($period, $from, $to);

        $submitted = $this->periodCountMap(
            $this->inquiryQuery($eventId, $croId),
            'created_at',
            $from,
            $to,
            $period
        );

        $resolved = $this->periodCountMap(
            $this->inquiryQuery($eventId, $croId)
                ->whereIn('status', [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed]),
            'updated_at',
            $from,
            $to,
            $period
        );

        $submittedCounts = collect($keys)->map(fn (string $key) => (int) ($submitted[$key] ?? 0))->values()->all();
        $resolvedCounts = collect($keys)->map(fn (string $key) => (int) ($resolved[$key] ?? 0))->values()->all();

        return [
            'submitted' => $submittedCounts,
            'resolved' => $resolvedCounts,
            'resolutionRate' => collect($keys)->map(function (string $key) use ($submitted, $resolved) {
                $submittedCount = (int) ($submitted[$key] ?? 0);
                $resolvedCount = (int) ($resolved[$key] ?? 0);

                return $submittedCount > 0 ? round(($resolvedCount / $submittedCount) * 100, 1) : 0;
            })->values()->all(),
        ];
    }

    /**
     * @return list<float|null>
     */
    private function responseTimeTrend(?int $eventId, ?int $croId, Carbon $from, Carbon $to, string $period): array
    {
        $keys = $this->periodKeys($period, $from, $to);
        $format = $period === 'month' ? '%Y-%m' : '%Y-%m-%d';

        $firstResponses = InquiryResponse::query()
            ->select('inquiry_id', DB::raw('MIN(created_at) as first_response_at'))
            ->groupBy('inquiry_id');

        $rows = DB::table('inquiries as i')
            ->joinSub($firstResponses, 'fr', 'fr.inquiry_id', '=', 'i.id')
            ->when($eventId, fn ($q) => $q->where('i.event_id', $eventId))
            ->when($croId, fn ($q) => $q->whereIn(
                'i.event_id',
                Event::query()->where('contact_person', $croId)->select('id')
            ))
            ->whereBetween('i.created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(i.created_at, '{$format}') as bucket, AVG(TIMESTAMPDIFF(MINUTE, i.created_at, fr.first_response_at)) as avg_minutes")
            ->groupBy('bucket')
            ->pluck('avg_minutes', 'bucket');

        return collect($keys)
            ->map(fn (string $key) => isset($rows[$key]) ? round((float) $rows[$key], 1) : null)
            ->values()
            ->all();
    }

    /**
     * @return list<float|null>
     */
    private function csatTrend(?int $eventId, Carbon $from, Carbon $to, string $period, ?int $scopeCroId = null): array
    {
        $keys = $this->periodKeys($period, $from, $to);
        $format = $period === 'month' ? '%Y-%m' : '%Y-%m-%d';

        $rows = Rating::query()
            ->when($scopeCroId, fn (Builder $q) => $q->whereIn(
                'event_id',
                Event::query()->where('contact_person', $scopeCroId)->select('id')
            ))
            ->when($eventId, fn (Builder $q) => $q->where('event_id', $eventId))
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as bucket, AVG(score) as avg_score")
            ->groupBy('bucket')
            ->pluck('avg_score', 'bucket');

        return collect($keys)
            ->map(fn (string $key) => isset($rows[$key]) ? round((float) $rows[$key], 1) : null)
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function inquiriesByEvent(?int $eventId, ?int $croId, Carbon $from, Carbon $to): array
    {
        return $this->inquiryQuery($eventId, $croId)
            ->whereBetween('inquiries.created_at', [$from, $to])
            ->whereNotNull('inquiries.event_id')
            ->join('events', 'inquiries.event_id', '=', 'events.id')
            ->selectRaw('events.name as label, COUNT(*) as count')
            ->groupBy('events.name')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->count])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Complaint>  $complaints
     * @return list<array{label: string, count: int}>
     */
    private function complaintCategoryBreakdown(Collection $complaints): array
    {
        $categories = [
            'Payment Issues' => 0,
            'Ticket Issues' => 0,
            'Refund Delays' => 0,
            'Event Information' => 0,
            'Account Issues' => 0,
            'Service Quality' => 0,
            'General' => 0,
        ];

        foreach ($complaints as $complaint) {
            $label = $this->classifyDetailedCategory($complaint->subject);
            $categories[$label] = ($categories[$label] ?? 0) + 1;
        }

        return collect($categories)
            ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Complaint>  $complaints
     * @return list<array{label: string, open: int, in_progress: int, resolved: int, closed: int}>
     */
    private function complaintsByStatusAndType(Collection $complaints): array
    {
        return $complaints
            ->groupBy(fn (Complaint $complaint) => $this->classifyComplaintType($complaint->subject))
            ->map(function (Collection $group, string $type) {
                $statusCounts = $group->countBy(fn (Complaint $c) => $c->status->value);

                return [
                    'label' => $type,
                    'open' => (int) ($statusCounts[SupportTicketStatusEnum::Open->value] ?? 0),
                    'in_progress' => (int) ($statusCounts[SupportTicketStatusEnum::InProgress->value] ?? 0),
                    'resolved' => (int) ($statusCounts[SupportTicketStatusEnum::Resolved->value] ?? 0),
                    'closed' => (int) ($statusCounts[SupportTicketStatusEnum::Closed->value] ?? 0),
                ];
            })
            ->sortByDesc(fn ($row) => $row['open'] + $row['in_progress'] + $row['resolved'] + $row['closed'])
            ->values()
            ->all();
    }

    /**
     * @return array{open: int, in_progress: int, resolved: int, closed: int}
     */
    private function countByStatus($query): array
    {
        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'open' => (int) ($counts[SupportTicketStatusEnum::Open->value] ?? 0),
            'in_progress' => (int) ($counts[SupportTicketStatusEnum::InProgress->value] ?? 0),
            'resolved' => (int) ($counts[SupportTicketStatusEnum::Resolved->value] ?? 0),
            'closed' => (int) ($counts[SupportTicketStatusEnum::Closed->value] ?? 0),
        ];
    }

    /**
     * @param  array{open: int, in_progress: int, resolved: int, closed: int}  $byStatus
     * @return list<array{label: string, count: int, key: string}>
     */
    private function formatStatusBreakdown(array $byStatus): array
    {
        return [
            ['label' => 'Open', 'count' => $byStatus['open'], 'key' => 'open'],
            ['label' => 'In Progress', 'count' => $byStatus['in_progress'], 'key' => 'in_progress'],
            ['label' => 'Resolved', 'count' => $byStatus['resolved'], 'key' => 'resolved'],
            ['label' => 'Closed', 'count' => $byStatus['closed'], 'key' => 'closed'],
        ];
    }

    /**
     * @return list<int>
     */
    private function periodCounts(Builder $query, string $dateColumn, Carbon $from, Carbon $to, string $period): array
    {
        $keys = $this->periodKeys($period, $from, $to);
        $map = $this->periodCountMap($query, $dateColumn, $from, $to, $period);

        return collect($keys)->map(fn (string $key) => (int) ($map[$key] ?? 0))->values()->all();
    }

    /**
     * @return \Illuminate\Support\Collection<string, int>
     */
    private function periodCountMap(Builder $query, string $dateColumn, Carbon $from, Carbon $to, string $period)
    {
        $format = $period === 'month' ? '%Y-%m' : '%Y-%m-%d';

        return (clone $query)
            ->whereBetween($dateColumn, [$from, $to])
            ->selectRaw("DATE_FORMAT({$dateColumn}, '{$format}') as bucket, COUNT(*) as count")
            ->groupBy('bucket')
            ->pluck('count', 'bucket');
    }

    /**
     * @return list<string>
     */
    private function periodKeys(string $period, Carbon $from, Carbon $to): array
    {
        $keys = collect();
        $cursor = $period === 'month'
            ? $from->copy()->startOfMonth()
            : $from->copy()->startOfDay();
        $end = $period === 'month'
            ? $to->copy()->startOfMonth()
            : $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $keys->push($period === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d'));
            $period === 'month' ? $cursor->addMonth() : $cursor->addDay();
            if ($keys->count() > 62) {
                break;
            }
        }

        return $keys->values()->all();
    }

    /**
     * @return list<string>
     */
    private function periodLabels(string $period, Carbon $from, Carbon $to): array
    {
        return collect($this->periodKeys($period, $from, $to))
            ->map(function (string $key) use ($period) {
                if ($period === 'month') {
                    return Carbon::createFromFormat('Y-m', $key)->format('M Y');
                }

                return Carbon::createFromFormat('Y-m-d', $key)->format('D j');
            })
            ->values()
            ->all();
    }

    private function averageResponseMinutes(?int $eventId, ?int $croId, Carbon $from, Carbon $to): ?float
    {
        $firstResponses = InquiryResponse::query()
            ->select('inquiry_id', DB::raw('MIN(created_at) as first_response_at'))
            ->groupBy('inquiry_id');

        $avg = DB::table('inquiries as i')
            ->joinSub($firstResponses, 'fr', 'fr.inquiry_id', '=', 'i.id')
            ->when($eventId, fn ($q) => $q->where('i.event_id', $eventId))
            ->when($croId, fn ($q) => $q->whereIn(
                'i.event_id',
                Event::query()->where('contact_person', $croId)->select('id')
            ))
            ->whereBetween('i.created_at', [$from, $to])
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

    /**
     * @return list<array{id: int, name: string}>
     */
    private function eventOptions(?int $scopeCroId = null): array
    {
        return Event::query()
            ->when($scopeCroId, fn (Builder $q) => $q->where('contact_person', $scopeCroId))
            ->when(! $scopeCroId, function (Builder $query) {
                $query->where(function (Builder $inner) {
                    $inner->whereIn('id', Inquiry::query()->whereNotNull('event_id')->select('event_id'))
                        ->orWhere(function (Builder $upcoming) {
                            $upcoming->visibleToAttendees()
                                ->whereDate('date', '>=', today()->subMonths(2))
                                ->whereDate('date', '<=', today()->addMonths(2));
                        });
                });
            })
            ->orderByDesc('date')
            ->limit(100)
            ->get(['id', 'name'])
            ->map(fn (Event $event) => ['id' => $event->id, 'name' => $event->name])
            ->values()
            ->all();
    }

    private function croOwnsEvent(int $croId, int $eventId): bool
    {
        return Event::query()
            ->where('id', $eventId)
            ->where('contact_person', $croId)
            ->exists();
    }

    private function classifyComplaintType(string $subject): string
    {
        $subject = strtolower($subject);

        if (str_contains($subject, 'refund') || str_contains($subject, 'payment') || str_contains($subject, 'billing') || str_contains($subject, 'charge')) {
            return 'Refund & Payment';
        }

        if (str_contains($subject, 'event') || str_contains($subject, 'ticket') || str_contains($subject, 'booking')) {
            return 'Event & Tickets';
        }

        if (str_contains($subject, 'account') || str_contains($subject, 'login') || str_contains($subject, 'technical') || str_contains($subject, 'password')) {
            return 'Account & Technical';
        }

        if (str_contains($subject, 'service') || str_contains($subject, 'staff') || str_contains($subject, 'support') || str_contains($subject, 'rude')) {
            return 'Service Quality';
        }

        return 'General';
    }

    private function classifyDetailedCategory(string $subject): string
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

        if (str_contains($subject, 'service') || str_contains($subject, 'staff') || str_contains($subject, 'rude')) {
            return 'Service Quality';
        }

        if (str_contains($subject, 'event') || str_contains($subject, 'venue') || str_contains($subject, 'schedule')) {
            return 'Event Information';
        }

        return 'General';
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
