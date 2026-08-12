<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\RefundRequest;
use App\Models\ticketBooking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CroCaseContextService
{
    private const PRIOR_TICKET_LIMIT = 8;

    /**
     * @return array{
     *     attendee: array{name: string, email: ?string, phone: ?string},
     *     event: ?array<string, mixed>,
     *     focusBooking: ?array<string, mixed>,
     *     priorTickets: list<array<string, mixed>>
     * }
     */
    public function forInquiry(Inquiry $inquiry): array
    {
        $inquiry->loadMissing(['user', 'event']);

        $focusBooking = $this->findBookingForEvent($inquiry->user, $inquiry->event);

        return $this->build($inquiry->user, $inquiry->event, $focusBooking);
    }

    /**
     * @return array{
     *     attendee: array{name: string, email: ?string, phone: ?string},
     *     event: ?array<string, mixed>,
     *     focusBooking: ?array<string, mixed>,
     *     priorTickets: list<array<string, mixed>>
     * }
     */
    public function forComplaint(Complaint $complaint): array
    {
        $complaint->loadMissing(['user', 'event']);

        $focusBooking = $complaint->event
            ? $this->findBookingForEvent($complaint->user, $complaint->event)
            : $this->latestBookingForUser($complaint->user);

        return $this->build(
            $complaint->user,
            $complaint->event ?? $focusBooking?->event,
            $focusBooking,
        );
    }

    /**
     * @return array{
     *     attendee: array{name: string, email: ?string, phone: ?string},
     *     event: ?array<string, mixed>,
     *     focusBooking: ?array<string, mixed>,
     *     priorTickets: list<array<string, mixed>>
     * }
     */
    public function forRefund(RefundRequest $refundRequest): array
    {
        $refundRequest->loadMissing([
            'user',
            'ticketBooking.event',
            'ticketBooking.ticketCategory',
            'ticketBooking.payment',
            'ticketBooking.refundRequest',
        ]);

        return $this->build(
            $refundRequest->user,
            $refundRequest->ticketBooking?->event,
            $refundRequest->ticketBooking,
        );
    }

    /**
     * @return array{
     *     attendee: array{name: string, email: ?string, phone: ?string},
     *     event: ?array<string, mixed>,
     *     focusBooking: ?array<string, mixed>,
     *     priorTickets: list<array<string, mixed>>
     * }
     */
    private function build(?User $user, ?Event $event, ?ticketBooking $focusBooking): array
    {
        $priorTickets = $this->priorTicketsForUser($user, $focusBooking?->id);

        return [
            'attendee' => $this->attendeePayload($user),
            'event' => $event ? $this->eventPayload($event) : null,
            'focusBooking' => $focusBooking ? $this->bookingPayload($focusBooking, true) : null,
            'priorTickets' => $priorTickets,
        ];
    }

    /**
     * @return array{name: string, email: ?string, phone: ?string}
     */
    private function attendeePayload(?User $user): array
    {
        return [
            'name' => $user?->full_name ?? 'Unknown attendee',
            'email' => $user?->email,
            'phone' => $user?->contact_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(Event $event): array
    {
        $status = (string) ($event->status ?? 'unknown');
        $reason = null;

        if ($event->isCancelled()) {
            $reason = filled($event->cancellation_reason) ? (string) $event->cancellation_reason : null;
        } elseif ($event->isPostponed()) {
            $reason = filled($event->postponement_reason) ? (string) $event->postponement_reason : null;
        }

        return [
            'name' => $event->name,
            'date' => $event->formattedScheduleDate('d M Y') ?? ($event->hasDateYetToBeScheduled() ? 'TBA' : '—'),
            'time' => filled($event->time) && ! $event->hasDateYetToBeScheduled()
                ? Carbon::parse($event->time)->format('g:i A')
                : null,
            'place' => $event->displayPlace(),
            'status' => $status,
            'statusLabel' => ucfirst(str_replace('_', ' ', $status)),
            'statusClass' => $this->eventStatusClass($event),
            'scheduleLabel' => $event->scheduleStatusLabel(),
            'reason' => $reason,
            'refundsAllowed' => (bool) $event->refunds_allowed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(ticketBooking $booking, bool $isFocus = false): array
    {
        $booking->loadMissing(['event', 'ticketCategory', 'payment', 'refundRequest']);

        $payment = $booking->payment;
        $paymentStatus = $payment?->status;
        $paymentMethod = $payment?->payment_method;

        return [
            'id' => $booking->id,
            'ticketNumber' => $booking->ticket_number,
            'category' => $booking->ticketCategory?->name ?? '—',
            'price' => number_format((float) $booking->ticket_price, 2),
            'statusLabel' => $booking->displayStatusLabel(),
            'statusClass' => $booking->displayStatusBadgeClasses(),
            'paymentStatus' => $paymentStatus instanceof PaymentStatusEnum
                ? ucfirst($paymentStatus->value)
                : '—',
            'paymentStatusClass' => $this->paymentStatusClass($paymentStatus),
            'paymentMethod' => $paymentMethod instanceof PaymentMethodEnum
                ? ucfirst($paymentMethod->value)
                : null,
            'paymentReference' => $payment?->reference,
            'purchasedAt' => $booking->created_at?->format('d M Y, H:i'),
            'eventName' => $booking->event?->name ?? '—',
            'eventDate' => $booking->event?->formattedScheduleDate('d M Y')
                ?? ($booking->event?->hasDateYetToBeScheduled() ? 'TBA' : '—'),
            'checkedIn' => $booking->isCheckedIn(),
            'refundPending' => $booking->refundRequest?->isPending() ?? false,
            'isFocus' => $isFocus,
        ];
    }

    private function findBookingForEvent(?User $user, ?Event $event): ?ticketBooking
    {
        if (! $user || ! $event) {
            return null;
        }

        return ticketBooking::query()
            ->with(['event', 'ticketCategory', 'payment', 'refundRequest'])
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->latest()
            ->first();
    }

    private function latestBookingForUser(?User $user): ?ticketBooking
    {
        if (! $user) {
            return null;
        }

        return ticketBooking::query()
            ->with(['event', 'ticketCategory', 'payment', 'refundRequest'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function priorTicketsForUser(?User $user, ?int $focusBookingId): array
    {
        if (! $user) {
            return [];
        }

        /** @var Collection<int, ticketBooking> $bookings */
        $bookings = ticketBooking::query()
            ->with(['event', 'ticketCategory', 'payment', 'refundRequest'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(self::PRIOR_TICKET_LIMIT)
            ->get();

        return $bookings
            ->map(fn (ticketBooking $booking) => $this->bookingPayload(
                $booking,
                $focusBookingId !== null && (int) $booking->id === (int) $focusBookingId,
            ))
            ->values()
            ->all();
    }

    private function eventStatusClass(Event $event): string
    {
        return match (true) {
            $event->isCancelled() => 'bg-rose-100 text-rose-700',
            $event->isPostponed() => 'bg-amber-100 text-amber-800',
            $event->isCompleted() => 'bg-slate-200 text-slate-700',
            $event->status === Event::STATUS_ONGOING => 'bg-sky-100 text-sky-700',
            default => 'bg-emerald-100 text-emerald-700',
        };
    }

    private function paymentStatusClass(?PaymentStatusEnum $status): string
    {
        return match ($status) {
            PaymentStatusEnum::Completed => 'bg-emerald-100 text-emerald-700',
            PaymentStatusEnum::Pending => 'bg-amber-100 text-amber-700',
            PaymentStatusEnum::Failed => 'bg-rose-100 text-rose-700',
            PaymentStatusEnum::Cancelled => 'bg-slate-100 text-slate-600',
            default => 'bg-slate-100 text-slate-600',
        };
    }
}
