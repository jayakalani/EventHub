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
        protected CartInventoryService $cartInventoryService,
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

        $checkoutItems = $this->snapshotCheckoutItems($cartItems);
        $cartItemIds = collect($checkoutItems)->pluck('cart_item_id')->all();

        $payment = Payment::create([
            'user_id' => $userId,
            'reference' => 'PAY-'.strtoupper(Str::random(10)),
            'amount' => $totalAmount,
            'currency' => config('services.stripe.currency', 'lkr'),
            'payment_method' => PaymentMethodEnum::Stripe,
            'purpose' => 'ticket_purchase',
            'status' => PaymentStatusEnum::Pending,
            'cart_item_ids' => $cartItemIds,
            'checkout_items' => $checkoutItems,
        ]);

        $this->extendHoldsForCheckout($cartItemIds);

        try {
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
        } catch (\Throwable $e) {
            $payment->update(['status' => PaymentStatusEnum::Cancelled]);

            throw $e;
        }

        $payment->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CartItem>  $cartItems
     */
    public function payWithWallet($cartItems, User $user): Payment
    {
        $totalAmount = $cartItems->sum(fn (CartItem $item) => (float) $item->ticketCategory->ticket_price * $item->quantity);
         //$totalAmount = $cartItems->sum(fn (CartItem $item) => $item->ticketCategory->effectivePrice() * $item->quantity);
        return DB::transaction(function () use ($cartItems, $user, $totalAmount) {
            $checkoutItems = $this->snapshotCheckoutItems($cartItems);

            $payment = Payment::create([
                'user_id' => $user->id,
                'reference' => 'PAY-'.strtoupper(Str::random(10)),
                'amount' => $totalAmount,
                'currency' => config('services.stripe.currency', 'lkr'),
                'payment_method' => PaymentMethodEnum::Wallet,
                'purpose' => 'ticket_purchase',
                'status' => PaymentStatusEnum::Pending,
                'cart_item_ids' => collect($checkoutItems)->pluck('cart_item_id')->all(),
                'checkout_items' => $checkoutItems,
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

            $lines = $this->checkoutLinesFor($payment);

            if ($lines === []) {
                throw new RuntimeException('Checkout lines for this payment are no longer available.');
            }

            $purchasedEventIds = collect($lines)->pluck('event_id')->unique()->filter()->values()->all();
            $soldOutCategoryIds = [];

            foreach ($lines as $line) {
                $cartItem = CartItem::query()
                    ->where('user_id', $payment->user_id)
                    ->whereKey((int) ($line['cart_item_id'] ?? 0))
                    ->lockForUpdate()
                    ->first();

                $category = $this->cartInventoryService->fulfillPaidLine($line, $cartItem);
                $quantity = (int) $line['quantity'];
                $unitPrice = (float) ($line['unit_price'] ?? $category->ticket_price);
                //$unitPrice = (float) ($line['unit_price'] ?? $category->effectivePrice());
                for ($i = 0; $i < $quantity; $i++) {
                    ticketBooking::create([
                        'user_id' => $payment->user_id,
                        'event_id' => (int) $line['event_id'],
                        'ticket_category_id' => (int) $line['ticket_category_id'],
                        'payment_id' => $payment->id,
                        'ticket_number' => $this->ticketQrService->generateTicketNumber(),
                        'ticket_price' => $unitPrice,
                        'status' => BookingStatusEnum::Confirmed,
                    ]);
                }

                if ((int) $category->no_of_available_tickets <= 0) {
                    $soldOutCategoryIds[] = $category->id;
                }

                $cartItem?->delete();
            }

            $payment->update([
                'status' => PaymentStatusEnum::Completed,
                'stripe_payment_intent_id' => $stripePaymentIntentId,
            ]);

            DB::afterCommit(function () use ($payment, $purchasedEventIds, $soldOutCategoryIds) {
                $payment->loadMissing('user');
                Mail::to($payment->user)->queue(new TicketPurchaseConfirmationMail($payment));
                $payment->user->notify(new \App\Notifications\TicketPurchasedNotification($payment));
                $payment->user->notify(new \App\Notifications\PaymentSuccessfulNotification($payment));
                app(OrganizerDashboardService::class)->notifyLowInventoryForEvents($purchasedEventIds);

                $organizerNotifications = app(OrganizerNotificationService::class);
                foreach (array_unique($soldOutCategoryIds) as $categoryId) {
                    $soldOutCategory = ticketCategory::query()->find($categoryId);
                    if ($soldOutCategory) {
                        $organizerNotifications->notifyTicketCategorySoldOut($soldOutCategory);
                    }
                }
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

    /**
     * @param  \Illuminate\Support\Collection<int, CartItem>  $cartItems
     * @return list<array{cart_item_id: int, event_id: int, ticket_category_id: int, quantity: int, unit_price: float, inventory_held: bool}>
     */
    private function snapshotCheckoutItems($cartItems): array
    {
        return $cartItems->map(fn (CartItem $item) => [
            'cart_item_id' => (int) $item->id,
            'event_id' => (int) $item->event_id,
            'ticket_category_id' => (int) $item->ticket_category_id,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->ticketCategory->ticket_price,
            //'unit_price' => $item->ticketCategory->effectivePrice(),
            'inventory_held' => (bool) $item->inventory_held,
        ])->values()->all();
    }

    /**
     * @return list<array{cart_item_id?: int, event_id: int, ticket_category_id: int, quantity: int, unit_price?: float, inventory_held?: bool}>
     */
    private function checkoutLinesFor(Payment $payment): array
    {
        $snapshot = $payment->checkout_items ?? [];

        if (is_array($snapshot) && $snapshot !== []) {
            return array_values($snapshot);
        }

        $cartItems = CartItem::query()
            ->where('user_id', $payment->user_id)
            ->whereIn('id', $payment->cart_item_ids ?? [])
            ->with('ticketCategory')
            ->lockForUpdate()
            ->get();

        return $this->snapshotCheckoutItems($cartItems);
    }

    /**
     * @param  list<int>  $cartItemIds
     */
    private function extendHoldsForCheckout(array $cartItemIds): void
    {
        if ($cartItemIds === []) {
            return;
        }

        CartItem::query()
            ->whereIn('id', $cartItemIds)
            ->update([
                'reserved_until' => now()->addHours(24),
            ]);
    }
}
