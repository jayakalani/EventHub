<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Event;
use App\Models\ticketBooking;
use App\Models\ticketCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CartController extends Controller
{
    private function validateTicketCategoryForBooking(ticketCategory $category, int $quantity): ?string
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

        if ($quantity > $category->no_of_available_tickets) {
            return "Only {$category->no_of_available_tickets} ticket(s) available for this category.";
        }

        return null;
    }

    /**
     * Reserve tickets (add to cart).
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_category_id' => ['required', 'exists:ticket_categories,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $category = ticketCategory::query()
            ->where('event_id', $event->id)
            ->findOrFail($validated['ticket_category_id']);

        $existingItem = CartItem::query()
            ->where('user_id', Auth::id())
            ->where('ticket_category_id', $category->id)
            ->first();

        $proposedQuantity = $validated['quantity'] + (int) ($existingItem?->quantity ?? 0);

        if ($error = $this->validateTicketCategoryForBooking($category, $proposedQuantity)) {
            return back()->withErrors(['quantity' => $error])->withInput();
        }

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $proposedQuantity,
                'unit_price' => $category->ticket_price,
            ]);
        } else {
            CartItem::create([
                'user_id' => Auth::id(),
                'event_id' => $event->id,
                'ticket_category_id' => $category->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $category->ticket_price,
            ]);
        }

        return back()->with('success', 'Tickets reserved successfully. Review them below or go to your cart.');
    }

    /**
     * Show cart with items grouped by event.
     */
    public function index(): View
    {
        $cartItems = CartItem::query()
            ->where('user_id', Auth::id())
            ->with(['event.host', 'event.eventCategory', 'ticketCategory'])
            ->latest()
            ->get()
            ->groupBy('event_id');

        $cartTotal = CartItem::query()
            ->where('user_id', Auth::id())
            ->get()
            ->sum(fn (CartItem $item) => $item->line_total);

        return view('attendee.cart.index', compact('cartItems', 'cartTotal'));
    }

    /**
     * Update reserved ticket quantity.
     */
    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($cartItem);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $category = $cartItem->ticketCategory;

        if ($error = $this->validateTicketCategoryForBooking($category, $validated['quantity'])) {
            return back()->withErrors(['quantity' => $error]);
        }

        $cartItem->update([
            'quantity' => $validated['quantity'],
            'unit_price' => $category->ticket_price,
        ]);

        return back()->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove item from cart.
     */
    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($cartItem);

        $cartItem->delete();

        return back()->with('success', 'Item removed from cart.');
    }

    /**
     * Pay for selected cart items (bulk checkout).
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['integer', 'exists:cart_items,id'],
        ]);

        $cartItems = CartItem::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $validated['cart_item_ids'])
            ->with(['ticketCategory', 'event'])
            ->get();

        if ($cartItems->isEmpty()) {
            return back()->withErrors(['cart_item_ids' => 'No valid cart items selected.']);
        }

        try {
            DB::transaction(function () use ($cartItems) {
                foreach ($cartItems as $cartItem) {
                    $category = ticketCategory::query()
                        ->lockForUpdate()
                        ->findOrFail($cartItem->ticket_category_id);

                    if ($error = $this->validateTicketCategoryForBooking($category, $cartItem->quantity)) {
                        throw new \RuntimeException($error);
                    }

                    ticketBooking::create([
                        'user_id' => Auth::id(),
                        'event_id' => $cartItem->event_id,
                        'ticket_category_id' => $cartItem->ticket_category_id,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $cartItem->unit_price,
                        'total_amount' => $cartItem->line_total,
                        'status' => 'confirmed',
                        'reference' => 'BK-'.strtoupper(Str::random(10)),
                    ]);

                    $category->decrement('no_of_available_tickets', $cartItem->quantity);
                    $cartItem->delete();
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()
            ->route('attendee.bookings.index')
            ->with('success', 'Payment successful! Your tickets have been confirmed.');
    }

    private function authorizeCartItem(CartItem $cartItem): void
    {
        if ((int) $cartItem->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}
