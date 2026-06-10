<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Mail\RefundRequestApprovedMail;
use App\Mail\RefundRequestDeclinedMail;
use App\Mail\RefundRequestSubmittedMail;
use App\Models\RefundRequest;
use App\Models\ticketBooking;
use App\Models\ticketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class RefundRequestService
{
    public function __construct(
        protected RefundPolicyService $refundPolicyService,
        protected WalletService $walletService,
    ) {}

    public function submit(ticketBooking $booking, string $reason): RefundRequest
    {
        if (! $booking->isCancellable()) {
            throw new RuntimeException('This ticket cannot be cancelled.');
        }

        $policy = $this->refundPolicyService->evaluate($booking);

        $refundRequest = DB::transaction(function () use ($booking, $reason, $policy) {
            $refundRequest = RefundRequest::create([
                'ticket_booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'reason' => $reason,
                'refund_percentage' => $policy->refundPercentage,
                'refund_amount' => $policy->refundAmount,
                'status' => $policy->status,
            ]);

            if ($policy->status === RefundRequestStatusEnum::AutoDeclined) {
                $booking->update(['status' => BookingStatusEnum::RefundDeclined]);
            }

            return $refundRequest;
        });

        $refundRequest->load(['user', 'ticketBooking.event', 'ticketBooking.ticketCategory']);

        if ($policy->requiresCroReview) {
            Mail::to($refundRequest->user)->queue(new RefundRequestSubmittedMail($refundRequest));
        } else {
            Mail::to($refundRequest->user)->queue(new RefundRequestDeclinedMail($refundRequest));
        }

        return $refundRequest;
    }

    public function approve(RefundRequest $refundRequest, User $reviewer, ?string $notes = null): void
    {
        if (! $refundRequest->isPending()) {
            throw new RuntimeException('This refund request has already been processed.');
        }

        DB::transaction(function () use ($refundRequest, $reviewer, $notes) {
            $refundRequest = RefundRequest::query()->lockForUpdate()->findOrFail($refundRequest->id);

            if (! $refundRequest->isPending()) {
                throw new RuntimeException('This refund request has already been processed.');
            }

            $booking = ticketBooking::query()
                ->lockForUpdate()
                ->findOrFail($refundRequest->ticket_booking_id);

            $category = ticketCategory::query()
                ->lockForUpdate()
                ->findOrFail($booking->ticket_category_id);

            $this->walletService->creditRefund($refundRequest);

            $booking->update(['status' => BookingStatusEnum::Refunded]);
            $category->increment('no_of_available_tickets');

            $refundRequest->update([
                'status' => RefundRequestStatusEnum::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'cro_notes' => $notes,
            ]);

            DB::afterCommit(function () use ($refundRequest) {
                $refundRequest->load(['user.wallet', 'ticketBooking.event', 'ticketBooking.ticketCategory', 'reviewer']);
                Mail::to($refundRequest->user)->queue(new RefundRequestApprovedMail($refundRequest));
            });
        });
    }

    public function decline(RefundRequest $refundRequest, User $reviewer, string $notes): void
    {
        if (trim($notes) === '') {
            throw new RuntimeException('A decline reason is required.');
        }

        if (! $refundRequest->isPending()) {
            throw new RuntimeException('This refund request has already been processed.');
        }

        DB::transaction(function () use ($refundRequest, $reviewer, $notes) {
            $refundRequest = RefundRequest::query()->lockForUpdate()->findOrFail($refundRequest->id);

            if (! $refundRequest->isPending()) {
                throw new RuntimeException('This refund request has already been processed.');
            }

            ticketBooking::query()
                ->where('id', $refundRequest->ticket_booking_id)
                ->update(['status' => BookingStatusEnum::RefundDeclined]);

            $refundRequest->update([
                'status' => RefundRequestStatusEnum::Declined,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'cro_notes' => $notes,
            ]);

            DB::afterCommit(function () use ($refundRequest) {
                $refundRequest->load(['user', 'ticketBooking.event', 'ticketBooking.ticketCategory', 'reviewer']);
                Mail::to($refundRequest->user)->queue(new RefundRequestDeclinedMail($refundRequest));
            });
        });
    }
}
