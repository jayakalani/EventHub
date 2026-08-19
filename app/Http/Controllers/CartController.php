<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Event;
use App\Models\Payment;
use App\Models\ticketCategory;
use App\Services\CartInventoryService;
use App\Services\StripeCheckoutService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class CartController extends Controller
{
    public function __construct(
        protected StripeCheckoutService $stripeCheckoutService,
        protected WalletService $walletService,
        protected CartInventoryService $cartInventoryService,
    ) {}

    private function validateTicketCategoryWindow(ticketCategory $category): ?string
    {
        if (! $category->is_active) {
            return 'This ticket category is not available for booking.';
        }

        $now = now();

        if ($category->booking_start && $now->lt($category->booking_start)) {
            return 'Booking for this category has not started yet.';
        }

        if ($category->booking_end && $now->gt($category->booking_end)) {
            return 'Booking for this category has ended.';
        }

        return null;
    }

    /**
     * Reserve tickets (add to cart) and hard-hold inventory.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $event->ensureBookable();

        $validated = $request->validate([
            'ticket_category_id' => ['required', 'exists:ticket_categories,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($validated, $event) {
                $category = ticketCategory::query()
                    ->where('event_id', $event->id)
                    ->lockForUpdate()
                    ->findOrFail($validated['ticket_category_id']);

                if ($error = $this->validateTicketCategoryWindow($category)) {
                    throw new RuntimeException($error);
                }

                $existingItem = CartItem::query()
                    ->where('user_id', Auth::id())
                    ->where('ticket_category_id', $category->id)
                    ->lockForUpdate()
                    ->first();

                $addQuantity = (int) $validated['quantity'];
                $reserveQuantity = $addQuantity;

                // Legacy soft-hold rows never decremented stock; claim the full line now.
                if ($existingItem && ! $existingItem->inventory_held) {
                    $reserveQuantity = (int) $existingItem->quantity + $addQuantity;
                }

                $this->cartInventoryService->reserve($category, $reserveQuantity);

                $reservedUntil = now()->addMinutes((int) config('cart.reservation_minutes', 30));

                if ($existingItem) {
                    $existingItem->update([
                        'quantity' => (int) $existingItem->quantity + $addQuantity,
                        'reserved_until' => $reservedUntil,
                        'inventory_held' => true,
                    ]);
                } else {
                    CartItem::create([
                        'user_id' => Auth::id(),
                        'event_id' => $event->id,
                        'ticket_category_id' => $category->id,
                        'quantity' => $addQuantity,
                        'reserved_until' => $reservedUntil,
                        'inventory_held' => true,
                    ]);
                }
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Tickets reserved successfully. Review them below or go to your cart.');
    }

    /**
     * Show cart with items grouped by event.
     */
    public function index(): View
    {
        $autoReleased = $this->cartInventoryService->releaseReservationExpiredForUser((int) Auth::id());

        $cartItems = CartItem::query()
            ->where('user_id', Auth::id())
            ->with(['event.host', 'event.eventCategory', 'ticketCategory'])
            ->latest()
            ->get()
            ->groupBy('event_id');

        $cartTotal = CartItem::query()
            ->where('user_id', Auth::id())
            ->with('ticketCategory')
            ->get()
            ->sum(fn (CartItem $item) => $item->line_total);

        $wallet = $this->walletService->getOrCreateWallet(Auth::user());
        $walletBalance = (float) $wallet->balance;

        $purchasableCartItemIds = $cartItems->flatten()
            ->filter(fn (CartItem $item) => ! $item->hasPurchaseDeadlinePassed())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $selectedCartItemIds = collect(session('cart.selected_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => in_array($id, $purchasableCartItemIds, true))
            ->values()
            ->all();

        $this->rememberSelectedCartItemIds($selectedCartItemIds);

        if ($autoReleased > 0 && ! session()->has('success')) {
            session()->now(
                'success',
                $autoReleased === 1
                    ? '1 expired reservation was cleared and tickets were released.'
                    : "{$autoReleased} expired reservations were cleared and tickets were released."
            );
        }

        return view('attendee.cart.index', compact('cartItems', 'cartTotal', 'walletBalance', 'selectedCartItemIds'));
    }

    /**
     * Persist selected cart item IDs in the session.
     */
    public function rememberSelection(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'cart_item_ids' => ['nullable', 'array'],
            'cart_item_ids.*' => ['integer', 'exists:cart_items,id'],
            'redirect_to' => ['nullable', 'string', 'in:wallet'],
        ]);

        $this->rememberSelectedCartItemIds($validated['cart_item_ids'] ?? []);

        if (($validated['redirect_to'] ?? null) === 'wallet') {
            return redirect()->route('attendee.wallet.index');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'selected_cart_item_ids' => session('cart.selected_item_ids', []),
            ]);
        }

        return back();
    }

    /**
     * Update reserved ticket quantity (adjusts inventory hold).
     */
    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($cartItem);

        if ($cartItem->hasPurchaseDeadlinePassed()) {
            return back()->withErrors(['quantity' => 'This cart item has expired and can no longer be updated.']);
        }

        if (Payment::cartItemHasPendingStripeCheckout((int) $cartItem->id)) {
            return back()->withErrors(['quantity' => 'This reservation is part of an in-progress payment. Complete or cancel checkout first.']);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $category = $cartItem->ticketCategory;

        if ($error = $this->validateTicketCategoryWindow($category)) {
            return back()->withErrors(['quantity' => $error]);
        }

        try {
            $this->cartInventoryService->adjustQuantity($cartItem, (int) $validated['quantity']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove item from cart and release inventory hold.
     */
    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($cartItem);

        if (Payment::cartItemHasPendingStripeCheckout((int) $cartItem->id)) {
            return back()->withErrors(['cart' => 'This reservation is part of an in-progress payment. Complete or cancel checkout first.']);
        }

        $remainingSelected = collect(session('cart.selected_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $cartItem->id)
            ->values()
            ->all();

        try {
            $this->cartInventoryService->releaseAndDelete($cartItem);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }

        $this->rememberSelectedCartItemIds($remainingSelected);

        return back()->with('success', 'Item removed from cart.');
    }

    /**
     * Remove expired reservations and release their inventory holds.
     */
    public function clearExpired(): RedirectResponse
    {
        $cleared = $this->cartInventoryService->clearExpiredForUser((int) Auth::id());

        $remainingSelected = collect(session('cart.selected_item_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(function (int $id) {
                return CartItem::query()
                    ->where('user_id', Auth::id())
                    ->whereKey($id)
                    ->exists();
            })
            ->values()
            ->all();

        $this->rememberSelectedCartItemIds($remainingSelected);

        if ($cleared === 0) {
            return back()->with('success', 'No expired reservations to clear.');
        }

        return back()->with(
            'success',
            $cleared === 1
                ? 'Removed 1 expired reservation and released those tickets.'
                : "Removed {$cleared} expired reservations and released those tickets."
        );
    }

    /**
     * Start Stripe Checkout for selected cart items.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $this->cartInventoryService->releaseExpired();

        $validated = $request->validate([
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['integer', 'exists:cart_items,id'],
            'payment_method' => ['required', 'in:stripe,wallet'],
        ]);

        $this->rememberSelectedCartItemIds($validated['cart_item_ids']);

        $cartItems = CartItem::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['cart_item_ids'])
            ->with(['ticketCategory', 'event'])
            ->get();

        if ($cartItems->isEmpty()) {
            return back()->withErrors(['cart_item_ids' => 'No valid cart items selected.']);
        }

        foreach ($cartItems as $cartItem) {
            if ($cartItem->hasPurchaseDeadlinePassed()) {
                return back()->withErrors(['checkout' => 'One or more selected tickets have expired. Remove them or refresh your cart.']);
            }

            if ($cartItem->event->isCancelled()) {
                return back()->withErrors(['checkout' => 'One or more events in your cart have been cancelled.']);
            }

            if ($error = $this->validateTicketCategoryWindow($cartItem->ticketCategory)) {
                return back()->withErrors(['checkout' => $error]);
            }
        }

        $cartTotal = $cartItems->sum(fn (CartItem $item) => $item->line_total);

        if ($validated['payment_method'] === 'wallet') {
            $wallet = $this->walletService->getOrCreateWallet(Auth::user());

            if ((float) $wallet->balance < $cartTotal) {
                return redirect()
                    ->route('attendee.wallet.index')
                    ->withErrors(['wallet' => 'Insufficient wallet balance. Please top up.']);
            }

            try {
                $this->stripeCheckoutService->payWithWallet($cartItems, Auth::user());
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors(['checkout' => $e->getMessage() ?: 'Unable to complete wallet payment.']);
            }

            session()->forget('cart.selected_item_ids');

            return redirect()
                ->route('attendee.bookings.index')
                ->with('success', 'Payment successful using your wallet! Your tickets are ready.');
        }

        if (! config('services.stripe.secret')) {
            return back()->withErrors(['checkout' => 'Payment is not configured. Please contact support.']);
        }

        try {
            $session = $this->stripeCheckoutService->createCheckoutSession($cartItems, (int) Auth::id());
        } catch (\Throwable $e) {
            report($e);

            $message = 'Unable to start Stripe checkout. Please try again.';

            if (str_contains($e->getMessage(), 'Could not resolve host')
                || str_contains($e->getMessage(), 'Could not connect to Stripe')) {
                $message = 'Unable to reach Stripe right now. Check your internet connection and try again.';
            } elseif (filled($e->getMessage())) {
                $message = $e->getMessage();
            }

            return back()->withErrors(['checkout' => $message]);
        }

        return redirect()->away($session->url);
    }

    private function authorizeCartItem(CartItem $cartItem): void
    {
        if ((int) $cartItem->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    private function rememberSelectedCartItemIds(array $ids): void
    {
        $ownedIds = CartItem::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        session(['cart.selected_item_ids' => $ownedIds]);
    }
}
