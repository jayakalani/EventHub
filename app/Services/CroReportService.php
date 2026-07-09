<?php

namespace App\Services;

use App\Enums\SupportTicketStatusEnum;
use App\Models\Complaint;
use App\Models\Inquiry;
use Illuminate\Support\Collection;

class CroReportService
{
    /**
     * @return array<string, mixed>
     */
    public function getAllReports(): array
    {
        return [
            'inquiries' => $this->getInquiryResolutionReport(),
            'complaints' => $this->getComplaintStatisticsReport(),
            'chartLabels' => $this->lastSixMonthLabels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getInquiryResolutionReport(): array
    {
        $total = Inquiry::count();
        $byStatus = $this->countByStatus(Inquiry::query());

        $resolvedOrClosed = $byStatus['resolved'] + $byStatus['closed'];
        $active = $byStatus['open'] + $byStatus['in_progress'];

        return [
            'total' => $total,
            'open' => $byStatus['open'],
            'inProgress' => $byStatus['in_progress'],
            'resolved' => $byStatus['resolved'],
            'closed' => $byStatus['closed'],
            'active' => $active,
            'resolvedOrClosed' => $resolvedOrClosed,
            'resolutionRate' => $total > 0 ? round(($resolvedOrClosed / $total) * 100, 1) : 0,
            'statusBreakdown' => $this->formatStatusBreakdown($byStatus),
            'resolutionTrend' => $this->monthlyInquiryResolutionTrend(),
            'submissionsTrend' => $this->monthlyCounts(Inquiry::class, 'created_at'),
            'byEvent' => Inquiry::query()
                ->join('events', 'inquiries.event_id', '=', 'events.id')
                ->selectRaw('events.name as label, COUNT(*) as count')
                ->groupBy('events.name')
                ->orderByDesc('count')
                ->limit(6)
                ->get()
                ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->count])
                ->values()
                ->all(),
            'recentInquiries' => Inquiry::with(['user', 'event', 'assignee'])
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
    public function getComplaintStatisticsReport(): array
    {
        $complaints = Complaint::all(['id', 'subject', 'status']);
        $total = $complaints->count();
        $byStatus = $this->countByStatus(Complaint::query());

        $byType = $complaints
            ->groupBy(fn (Complaint $complaint) => $this->classifyComplaintType($complaint->subject))
            ->map(fn (Collection $group, string $type) => [
                'label' => $type,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'total' => $total,
            'open' => $byStatus['open'],
            'inProgress' => $byStatus['in_progress'],
            'resolved' => $byStatus['resolved'],
            'closed' => $byStatus['closed'],
            'statusBreakdown' => $this->formatStatusBreakdown($byStatus),
            'typeBreakdown' => $byType,
            'submissionsTrend' => $this->monthlyCounts(Complaint::class, 'created_at'),
            'statusByType' => $this->complaintsByStatusAndType(),
            'recentComplaints' => Complaint::with(['user', 'assignee'])
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
     * @return array{submitted: list<int>, resolved: list<float>}
     */
    private function monthlyInquiryResolutionTrend(): array
    {
        $keys = $this->lastSixMonthKeys();
        $since = now()->subMonths(5)->startOfMonth();

        $submitted = Inquiry::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        $resolved = Inquiry::query()
            ->whereIn('status', [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed])
            ->where('updated_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        $submittedCounts = collect($keys)->map(fn (string $key) => (int) ($submitted[$key] ?? 0))->values()->all();
        $resolutionRates = collect($keys)->map(function (string $key) use ($submitted, $resolved) {
            $submittedCount = (int) ($submitted[$key] ?? 0);
            $resolvedCount = (int) ($resolved[$key] ?? 0);

            return $submittedCount > 0 ? round(($resolvedCount / $submittedCount) * 100, 1) : 0;
        })->values()->all();

        return [
            'submitted' => $submittedCounts,
            'resolved' => collect($keys)->map(fn (string $key) => (int) ($resolved[$key] ?? 0))->values()->all(),
            'resolutionRate' => $resolutionRates,
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @return list<int>
     */
    private function monthlyCounts(string $modelClass, string $dateColumn): array
    {
        $keys = $this->lastSixMonthKeys();

        $counts = $modelClass::query()
            ->where($dateColumn, '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        return collect($keys)
            ->map(fn (string $key) => (int) ($counts[$key] ?? 0))
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, open: int, in_progress: int, resolved: int, closed: int}>
     */
    private function complaintsByStatusAndType(): array
    {
        return Complaint::all(['subject', 'status'])
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

    /**
     * @return list<string>
     */
    private function lastSixMonthLabels(): array
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('M Y'))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function lastSixMonthKeys(): array
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('Y-m'))
            ->values()
            ->all();
    }
}
