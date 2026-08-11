<?php

namespace App\Services;

use App\Enums\RefundRequestStatusEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\RefundRequest;
use App\Support\CroReplyTemplates;

class CroHandoffService
{
    /**
     * Build a CRO handoff checklist for a postponed or cancelled event.
     *
     * @return array{
     *     event: array{id: int, name: string, status: string, statusLabel: string, date: ?string, reason: ?string},
     *     type: string,
     *     openInquiries: list<array{id: int, subject: string, href: string}>,
     *     pendingRefunds: list<array{id: int, attendee: string, amount: string, href: string}>,
     *     suggestedReply: string,
     *     checklist: list<array{key: string, label: string, done: bool, href: ?string, count: int}>,
     *     summary: array{openInquiries: int, pendingRefunds: int, remaining: int}
     * }
     */
    public function forEvent(Event $event): array
    {
        $type = $event->isCancelled() ? 'cancelled' : ($event->isPostponed() ? 'postponed' : 'updated');

        $openInquiries = Inquiry::query()
            ->where('event_id', $event->id)
            ->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])
            ->oldest()
            ->get(['id', 'subject'])
            ->map(fn (Inquiry $inquiry) => [
                'id' => $inquiry->id,
                'subject' => $inquiry->subject,
                'href' => route('cro.inquiries.show', $inquiry),
            ])
            ->values()
            ->all();

        $pendingRefunds = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Pending)
            ->whereHas('ticketBooking', fn ($q) => $q->where('event_id', $event->id))
            ->with(['user', 'ticketBooking'])
            ->oldest()
            ->get()
            ->map(fn (RefundRequest $refund) => [
                'id' => $refund->id,
                'attendee' => $refund->user?->full_name ?? 'Attendee',
                'amount' => 'Rs '.number_format((float) $refund->refund_amount, 2),
                'href' => route('cro.refund-requests.show', $refund),
            ])
            ->values()
            ->all();

        $reason = $event->isCancelled()
            ? ($event->cancellation_reason ?: null)
            : ($event->postponement_reason ?: null);

        $suggestedReply = $this->suggestedReply($type, $event, $reason);

        $checklist = [
            [
                'key' => 'inquiries',
                'label' => 'Respond to open inquiries',
                'done' => count($openInquiries) === 0,
                'href' => route('cro.inquiries.index', ['event' => $event->id, 'status' => SupportTicketStatusEnum::Open->value]),
                'count' => count($openInquiries),
            ],
            [
                'key' => 'refunds',
                'label' => 'Review pending refunds',
                'done' => count($pendingRefunds) === 0,
                'href' => route('cro.refund-requests.index', ['event' => $event->id, 'status' => 'pending']),
                'count' => count($pendingRefunds),
            ],
            [
                'key' => 'reply',
                'label' => 'Use suggested attendee reply for consistency',
                'done' => false,
                'href' => null,
                'count' => 0,
            ],
        ];

        $remaining = (count($openInquiries) > 0 ? 1 : 0) + (count($pendingRefunds) > 0 ? 1 : 0);

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => (string) $event->status,
                'statusLabel' => ucfirst(str_replace('_', ' ', (string) $event->status)),
                'date' => $event->formattedScheduleDate('d M Y'),
                'reason' => $reason,
            ],
            'type' => $type,
            'openInquiries' => $openInquiries,
            'pendingRefunds' => $pendingRefunds,
            'suggestedReply' => $suggestedReply,
            'checklist' => $checklist,
            'summary' => [
                'openInquiries' => count($openInquiries),
                'pendingRefunds' => count($pendingRefunds),
                'remaining' => $remaining,
            ],
        ];
    }

    /**
     * Active handoffs for a CRO: postponed/cancelled assigned events with remaining work.
     *
     * @return list<array<string, mixed>>
     */
    public function activeForCro(int $croId, int $limit = 5): array
    {
        $events = Event::query()
            ->where('contact_person', $croId)
            ->whereIn('status', [Event::STATUS_POSTPONED, Event::STATUS_CANCELLED])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return $events
            ->map(fn (Event $event) => $this->forEvent($event) + [
                'href' => route('cro.handoffs.show', $event),
            ])
            ->filter(fn (array $handoff) => ($handoff['summary']['remaining'] ?? 0) > 0)
            ->take($limit)
            ->values()
            ->all();
    }

    private function suggestedReply(string $type, Event $event, ?string $reason): string
    {
        $eventName = $event->name;
        $reasonLine = filled($reason) ? ' Reason: '.$reason : '';

        if ($type === 'cancelled') {
            return "We're writing to let you know that \"{$eventName}\" has been cancelled.{$reasonLine} If you hold a ticket, please check My Bookings for refund options, or reply here and we'll help you next.";
        }

        if ($type === 'postponed') {
            $schedule = $event->scheduleStatusLabel();

            return "We're writing to let you know that \"{$eventName}\" has been postponed. {$schedule}.{$reasonLine} Please check My Bookings for keep-ticket or full-refund options. Reply here if you need help.";
        }

        return CroReplyTemplates::forInquiries()[0]['body'] ?? 'Thank you for contacting EventHub support. We are reviewing your request.';
    }
}
