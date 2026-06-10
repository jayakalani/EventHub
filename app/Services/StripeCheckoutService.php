<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Mail\TicketPurchaseConfirmationMail;
use App\Models\CartItem;
use App\Models\Payment;
use App\Models\ticketBooking;
use App\Models\ticketCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeCheckoutService
{
    public function __construct(
        protected TicketQrService $ticketQrService
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CartItem>  $cartItems
     */
    public function createCheckoutSession($cartItems, int $userId): Session
    {
        $lineItems = [];
        $totalAmount = 0;

        foreach ($cartItems as $cartItem) {
            $unitPrice = (float) $cartItem->ticketCategory->ticket_price;
            $totalAmount += $unitPrice * $cartItem->quantity;

            $lineItems[] = [
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'lkr'),
                    'product_data' => [
                        'name' => $cartItem->event->name.' — '.$cartItem->ticketCategory->name,
                        'description' => 'Event ticket · '.$cartItem->event->date.' · '.$cartItem->event->place,
                    ],
                    'unit_amount' => $this->toStripeAmount($unitPrice),
                ],
                'quantity' => $cartItem->quantity,
            ];
        }

        $payment = Payment::create([
            'user_id' => $userId,
            'reference' => 'PAY-'.strtoupper(Str::random(10)),
            'amount' => $totalAmount,
            'currency' => config('services.stripe.currency', 'lkr'),
            'status' => PaymentStatusEnum::Pending,
            'cart_item_ids' => $cartItems->pluck('id')->values()->all(),
        ]);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('attendee.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('attendee.checkout.cancel').'?payment_id='.$payment->id,
            'client_reference_id' => (string) $userId,
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'user_id' => (string) $userId,
            ],
        ]);

        $payment->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    public function fulfillPayment(Payment $payment, ?string $stripePaymentIntentId = null): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        DB::transaction(function () use ($payment, $stripePaymentIntentId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->isCompleted()) {
                return;
            }

            $cartItems = CartItem::query()
                ->where('user_id', $payment->user_id)
                ->whereIn('id', $payment->cart_item_ids)
                ->with(['ticketCategory', 'event'])
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw new RuntimeException('Cart items for this payment are no longer available.');
            }

            foreach ($cartItems as $cartItem) {
                $category = ticketCategory::query()
                    ->lockForUpdate()
                    ->findOrFail($cartItem->ticket_category_id);

                if ($cartItem->quantity > $category->no_of_available_tickets) {
                    throw new RuntimeException("Only {$category->no_of_available_tickets} ticket(s) available for {$category->name}.");
                }

                $unitPrice = (float) $category->ticket_price;

                for ($i = 0; $i < $cartItem->quantity; $i++) {
                    ticketBooking::create([
                        'user_id' => $payment->user_id,
                        'event_id' => $cartItem->event_id,
                        'ticket_category_id' => $cartItem->ticket_category_id,
                        'payment_id' => $payment->id,
                        'ticket_number' => $this->ticketQrService->generateTicketNumber(),
                        'ticket_price' => $unitPrice,
                        'status' => BookingStatusEnum::Confirmed,
                    ]);
                }

                $category->decrement('no_of_available_tickets', $cartItem->quantity);
                $cartItem->delete();
            }

            $payment->update([
                'status' => PaymentStatusEnum::Completed,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
            ]);

            DB::afterCommit(function () use ($payment) {
                Mail::to($payment->user)->queue(new TicketPurchaseConfirmationMail($payment));
            });
        });
    }

    public function markPaymentCancelled(Payment $payment): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        $payment->update(['status' => PaymentStatusEnum::Cancelled]);
    }

    private function toStripeAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
