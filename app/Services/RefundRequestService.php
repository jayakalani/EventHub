<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\AttendeeNotificationCategory;
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
        protected AuditLogService $auditLogService,
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

        app(AttendeeNotificationService::class)->send(
            $refundRequest->user,
            AttendeeNotificationCategory::Refund,
            'refund_request_received',
            'Your refund request for "'.($refundRequest->ticketBooking?->event?->name ?? 'an event').'" was received.',
            route('attendee.bookings.index'),
            ['refund_request_id' => $refundRequest->id],
        );

        if (! $policy->requiresCroReview) {
            app(AttendeeNotificationService::class)->send(
                $refundRequest->user,
                AttendeeNotificationCategory::Refund,
                'refund_rejected',
                'Your refund request for "'.($refundRequest->ticketBooking?->event?->name ?? 'an event').'" was rejected.',
                route('attendee.bookings.index'),
                ['refund_request_id' => $refundRequest->id],
            );
        }

        return $refundRequest;
    }

    /**
     * Immediate full wallet refund for a postponed event — no CRO review.
     */
    public function refundDueToPostponement(ticketBooking $booking): RefundRequest
    {
        $booking->loadMissing('event', 'refundRequest', 'user', 'ticketCategory');

        if (! $booking->isPostponementRefundable()) {
            throw new RuntimeException('This ticket is not eligible for a postponement refund.');
        }

        return DB::transaction(function () use ($booking) {
            $booking = ticketBooking::query()->lockForUpdate()->findOrFail($booking->id);
            $booking->loadMissing('event', 'refundRequest', 'user');

            if (! $booking->isPostponementRefundable()) {
                throw new RuntimeException('This ticket is not eligible for a postponement refund.');
            }

            $amount = (float) $booking->ticket_price;

            $refundRequest = RefundRequest::create([
                'ticket_booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'reason' => 'Full refund requested due to event postponement.',
                'refund_percentage' => 100,
                'refund_amount' => $amount,
                'status' => RefundRequestStatusEnum::Approved,
                'reviewed_by' => null,
                'reviewed_at' => now(),
                'cro_notes' => 'Automatically approved: full refund for postponed event (no CRO review).',
            ]);

            ticketCategory::query()
                ->lockForUpdate()
                ->where('id', $booking->ticket_category_id)
                ->increment('no_of_available_tickets');

            $this->walletService->credit(
                $booking->user,
                $amount,
                'Full refund for postponed event: '.$booking->event->name.' (Ticket '.$booking->ticket_number.')',
                $refundRequest,
            );

            $booking->update(['status' => BookingStatusEnum::Refunded]);

            $this->auditLogService->logPostponementRefund($booking, $refundRequest);

            DB::afterCommit(function () use ($refundRequest) {
                $refundRequest->load(['user.wallet', 'ticketBooking.event', 'ticketBooking.ticketCategory']);
                Mail::to($refundRequest->user)->queue(new RefundRequestApprovedMail($refundRequest));
                $refundRequest->user->notify(new \App\Notifications\RefundApprovedNotification($refundRequest));
                $refundRequest->user->notify(new \App\Notifications\RefundCompletedNotification($refundRequest));
                if ($refundRequest->ticketBooking) {
                    $refundRequest->user->notify(new \App\Notifications\TicketRefundedNotification($refundRequest->ticketBooking));
                }
            });

            return $refundRequest;
        });
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

            DB::afterCommit(function () use ($refundRequest, $booking) {
                $refundRequest->load(['user.wallet', 'ticketBooking.event', 'ticketBooking.ticketCategory', 'reviewer']);
                Mail::to($refundRequest->user)->queue(new RefundRequestApprovedMail($refundRequest));
                $refundRequest->user->notify(new \App\Notifications\RefundApprovedNotification($refundRequest));
                $refundRequest->user->notify(new \App\Notifications\RefundCompletedNotification($refundRequest));
                $refundRequest->user->notify(new \App\Notifications\TicketRefundedNotification($booking));
                $refundRequest->user->notify(new \App\Notifications\TicketCancelledNotification($booking));
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
                app(AttendeeNotificationService::class)->send(
                    $refundRequest->user,
                    AttendeeNotificationCategory::Refund,
                    'refund_rejected',
                    'Your refund request for "'.($refundRequest->ticketBooking?->event?->name ?? 'an event').'" was rejected.',
                    route('attendee.bookings.index'),
                    [
                        'refund_request_id' => $refundRequest->id,
                        'reason' => $refundRequest->cro_notes,
                    ],
                );
            });
        });
    }
}
