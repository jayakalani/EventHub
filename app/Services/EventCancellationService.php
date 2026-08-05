<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Mail\EventCancelledMail;
use App\Models\CartItem;
use App\Models\Event;
use App\Models\ticketBooking;
use App\Models\ticketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class EventCancellationService
{
    public function __construct(
        protected WalletService $walletService,
        protected CartInventoryService $cartInventoryService,
    ) {}

    public function cancel(Event $event, string $reason): void
    {
        if ($event->isCancelled()) {
            throw new RuntimeException('This event is already cancelled.');
        }

        DB::transaction(function () use ($event, $reason) {
            $event->update([
                'status' => Event::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            $cartItems = CartItem::query()
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->get();

            $this->cartInventoryService->releaseAndDeleteMany($cartItems);

            $bookings = ticketBooking::query()
                ->where('event_id', $event->id)
                ->where('status', BookingStatusEnum::Confirmed)
                ->with(['user', 'ticketCategory', 'refundRequest'])
                ->lockForUpdate()
                ->get();

            $refundsByUser = [];

            foreach ($bookings as $booking) {
                if ($booking->refundRequest?->isPending()) {
                    $booking->refundRequest->update([
                        'status' => RefundRequestStatusEnum::Approved,
                        'reviewed_at' => now(),
                        'cro_notes' => 'Automatically resolved due to event cancellation.',
                    ]);
                }

                ticketCategory::query()
                    ->lockForUpdate()
                    ->where('id', $booking->ticket_category_id)
                    ->increment('no_of_available_tickets');

                $this->walletService->credit(
                    $booking->user,
                    (float) $booking->ticket_price,
                    'Full refund for cancelled event: '.$event->name.' (Ticket '.$booking->ticket_number.')',
                    $booking,
                );

                $booking->update(['status' => BookingStatusEnum::EventCancelled]);

                $refundsByUser[$booking->user_id]['user'] = $booking->user;
                $refundsByUser[$booking->user_id]['total'] = ($refundsByUser[$booking->user_id]['total'] ?? 0) + (float) $booking->ticket_price;
            }

            $event->refresh();

            DB::afterCommit(function () use ($event, $reason, $refundsByUser) {
                $purchasers = ticketBooking::query()
                    ->where('event_id', $event->id)
                    ->with('user')
                    ->get()
                    ->groupBy('user_id');

                foreach ($purchasers as $userId => $userBookings) {
                    /** @var User $user */
                    $user = $userBookings->first()->user;

                    Mail::to($user)->queue(new EventCancelledMail(
                        $event,
                        $user,
                        $reason,
                        $userBookings,
                        (float) ($refundsByUser[$userId]['total'] ?? 0),
                    ));

                    foreach ($userBookings as $booking) {
                        $user->notify(new \App\Notifications\TicketCancelledNotification($booking));
                        $user->notify(new \App\Notifications\TicketRefundedNotification($booking));
                    }
                }

                app(EventNotificationService::class)->notifyEventCancelled($event, $reason);
                app(CroNotificationService::class)->notifyEventCancelled($event, $reason);
            });
        });
    }
}
