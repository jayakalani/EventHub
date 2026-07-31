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

        $event = $booking->event;
        $ticketPrice = (float) $booking->ticket_price;
        $now = now();
        $eventDayStart = Carbon::parse($event->date)->startOfDay();

        if (! $event->refunds_allowed) {
            return new RefundPolicyResult(
                refundPercentage: 0,
                refundAmount: 0,
                status: RefundRequestStatusEnum::AutoDeclined,
                policyLabel: 'Refunds are not available for this event.',
                requiresCroReview: false,
            );
        }

        if ($now->gte($eventDayStart)) {
            return new RefundPolicyResult(
                refundPercentage: 0,
                refundAmount: 0,
                status: RefundRequestStatusEnum::AutoDeclined,
                policyLabel: 'Refunds are not available on or after the event date.',
                requiresCroReview: false,
            );
        }

        $fullDays = (int) $event->refund_full_days_before_close;
        $fullPercentage = (int) $event->refund_full_percentage;
        $partialPercentage = (int) $event->refund_partial_percentage;

        $bookingCloseDate = $booking->ticketCategory->booking_end
            ? Carbon::parse($booking->ticketCategory->booking_end)
            : $eventDayStart->copy();

        $fullRefundCutoff = $bookingCloseDate->copy()->subDays($fullDays);

        if ($now->lt($fullRefundCutoff)) {
            return $this->buildResult(
                $fullPercentage,
                $ticketPrice,
                "Full refund ({$fullPercentage}%) — more than {$fullDays} days before booking closes.",
            );
        }

        return $this->buildResult(
            $partialPercentage,
            $ticketPrice,
            "Partial refund ({$partialPercentage}%) — within {$fullDays} days of booking closing date.",
        );
    }

    private function buildResult(int $percentage, float $ticketPrice, string $eligibleLabel): RefundPolicyResult
    {
        $percentage = max(0, min(100, $percentage));

        if ($percentage <= 0) {
            return new RefundPolicyResult(
                refundPercentage: 0,
                refundAmount: 0,
                status: RefundRequestStatusEnum::AutoDeclined,
                policyLabel: 'No refund is available under this event\'s policy at this time.',
                requiresCroReview: false,
            );
        }

        $refundAmount = round($ticketPrice * ($percentage / 100), 2);

        return new RefundPolicyResult(
            refundPercentage: $percentage,
            refundAmount: $refundAmount,
            status: RefundRequestStatusEnum::Pending,
            policyLabel: $eligibleLabel,
            requiresCroReview: true,
        );
    }
}
