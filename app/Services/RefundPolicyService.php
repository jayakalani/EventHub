<?php

namespace App\Services;

use App\Enums\RefundRequestStatusEnum;
use App\Models\ticketBooking;
use Carbon\Carbon;

class RefundPolicyResult
{
    public function __construct(
        public readonly int $refundPercentage,
        public readonly float $refundAmount,
        public readonly RefundRequestStatusEnum $status,
        public readonly string $policyLabel,
        public readonly bool $requiresCroReview,
    ) {}
}

class RefundPolicyService
{
    public function evaluate(ticketBooking $booking): RefundPolicyResult
    {
        $booking->loadMissing(['event', 'ticketCategory']);

        $ticketPrice = (float) $booking->ticket_price;
        $now = now();
        $eventDate = Carbon::parse($booking->event->date)->endOfDay();

        if ($now->gt($eventDate)) {
            return new RefundPolicyResult(
                refundPercentage: 0,
                refundAmount: 0,
                status: RefundRequestStatusEnum::AutoDeclined,
                policyLabel: 'Refunds are not available after the event date.',
                requiresCroReview: false,
            );
        }

        $bookingCloseDate = $booking->ticketCategory->booking_end
            ? Carbon::parse($booking->ticketCategory->booking_end)
            : $eventDate->copy()->startOfDay();

        $sevenDaysBeforeClose = $bookingCloseDate->copy()->subDays(7);

        if ($now->lt($sevenDaysBeforeClose)) {
            return new RefundPolicyResult(
                refundPercentage: 100,
                refundAmount: $ticketPrice,
                status: RefundRequestStatusEnum::Pending,
                policyLabel: 'Full refund (100%) — more than 7 days before booking closes.',
                requiresCroReview: true,
            );
        }

        $refundAmount = round($ticketPrice * 0.75, 2);

        return new RefundPolicyResult(
            refundPercentage: 75,
            refundAmount: $refundAmount,
            status: RefundRequestStatusEnum::Pending,
            policyLabel: 'Partial refund (75%) — within 7 days of booking closing date.',
            requiresCroReview: true,
        );
    }
}
