<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Mail\TicketPurchaseConfirmationMail;
use App\Mail\WalletTopupConfirmationMail;
use App\Models\CartItem;
use App\Models\Payment;
use App\Models\ticketBooking;
use App\Models\ticketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeCheckoutService
{
    public function __construct(
        protected TicketQrService $ticketQrService,
        protected WalletService $walletService,
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
            'payment_method' => PaymentMethodEnum::Stripe,
            'purpose' => 'ticket_purchase',
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

    /**
     * @param  \Illuminate\Support\Collection<int, CartItem>  $cartItems
     */
    public function payWithWallet($cartItems, User $user): Payment
    {
        $totalAmount = $cartItems->sum(fn (CartItem $item) => (float) $item->ticketCategory->ticket_price * $item->quantity);

        return DB::transaction(function () use ($cartItems, $user, $totalAmount) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'reference' => 'PAY-'.strtoupper(Str::random(10)),
                'amount' => $totalAmount,
                'currency' => config('services.stripe.currency', 'lkr'),
                'payment_method' => PaymentMethodEnum::Wallet,
                'purpose' => 'ticket_purchase',
                'status' => PaymentStatusEnum::Pending,
                'cart_item_ids' => $cartItems->pluck('id')->values()->all(),
            ]);

            $this->walletService->debit(
                $user,
                $totalAmount,
                'Ticket purchase '.$payment->reference,
                $payment,
            );

            $this->fulfillPayment($payment);

            return $payment;
        });
    }

    public function createWalletTopupSession(User $user, float $amount): Session
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference' => 'TOP-'.strtoupper(Str::random(10)),
            'amount' => $amount,
            'currency' => config('services.stripe.currency', 'lkr'),
            'payment_method' => PaymentMethodEnum::Stripe,
            'purpose' => 'wallet_topup',
            'status' => PaymentStatusEnum::Pending,
            'cart_item_ids' => [],
        ]);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'lkr'),
                    'product_data' => [
                        'name' => config('app.name').' Wallet Top-up',
                        'description' => 'Add funds to your EventHub wallet',
                    ],
                    'unit_amount' => $this->toStripeAmount($amount),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('attendee.wallet.topup.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('attendee.wallet.index').'?topup_cancelled=1',
            'client_reference_id' => (string) $user->id,
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'user_id' => (string) $user->id,
                'purpose' => 'wallet_topup',
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

        if ($payment->purpose === 'wallet_topup') {
            $this->fulfillWalletTopup($payment, $stripePaymentIntentId);

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

            $purchasedEventIds = $cartItems->pluck('event_id')->unique()->filter()->values()->all();

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

            DB::afterCommit(function () use ($payment, $purchasedEventIds) {
                $payment->loadMissing('user');
                Mail::to($payment->user)->queue(new TicketPurchaseConfirmationMail($payment));
                $payment->user->notify(new \App\Notifications\TicketPurchasedNotification($payment));
                $payment->user->notify(new \App\Notifications\PaymentSuccessfulNotification($payment));
                app(OrganizerDashboardService::class)->notifyLowInventoryForEvents($purchasedEventIds);
            });
        });
    }

    public function fulfillWalletTopup(Payment $payment, ?string $stripePaymentIntentId = null): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        DB::transaction(function () use ($payment, $stripePaymentIntentId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->isCompleted()) {
                return;
            }

            $user = User::query()->lockForUpdate()->findOrFail($payment->user_id);

            $this->walletService->credit(
                $user,
                (float) $payment->amount,
                'Wallet top-up '.$payment->reference,
                $payment,
            );

            $payment->update([
                'status' => PaymentStatusEnum::Completed,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
            ]);

            DB::afterCommit(function () use ($payment) {
                $payment->load('user.wallet');
                Mail::to($payment->user)->queue(new WalletTopupConfirmationMail($payment));
            });
        });
    }

    public function markPaymentCancelled(Payment $payment): void
    {
        if ($payment->isCompleted()) {
            return;
        }

        $payment->update(['status' => PaymentStatusEnum::Cancelled]);

        $payment->loadMissing('user');
        if ($payment->user) {
            $payment->user->notify(new \App\Notifications\PaymentFailedNotification($payment));
        }
    }

    private function toStripeAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
