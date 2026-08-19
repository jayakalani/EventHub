<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Payment;
use App\Models\ticketCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CartInventoryService
{
    /**
     * Lock inventory for a quantity of tickets (hard hold).
     *
     * @throws RuntimeException when not enough stock remains
     */
    public function reserve(ticketCategory $category, int $quantity): ticketCategory
    {
        if ($quantity < 1) {
            throw new RuntimeException('Reservation quantity must be at least 1.');
        }

        return DB::transaction(function () use ($category, $quantity) {
            $locked = ticketCategory::query()
                ->lockForUpdate()
                ->findOrFail($category->id);

            $available = (int) $locked->no_of_available_tickets;

            if ($quantity > $available) {
                throw new RuntimeException(
                    $available > 0
                        ? "Only {$available} ticket(s) available for this category."
                        : 'This ticket category is sold out.'
                );
            }

            $locked->decrement('no_of_available_tickets', $quantity);
            $locked->refresh();

            if ((int) $locked->no_of_available_tickets <= 0) {
                DB::afterCommit(function () use ($locked) {
                    app(OrganizerNotificationService::class)
                        ->notifyTicketCategorySoldOut($locked->fresh() ?? $locked);
                });
            }

            return $locked;
        });
    }

    /**
     * Return held tickets to available stock (never above category capacity).
     */
    public function release(ticketCategory|int $category, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $categoryId = $category instanceof ticketCategory ? $category->id : $category;

        DB::transaction(function () use ($categoryId, $quantity) {
            $locked = ticketCategory::query()
                ->lockForUpdate()
                ->find($categoryId);

            if (! $locked) {
                return;
            }

            $available = (int) $locked->no_of_available_tickets;
            $capacity = (int) $locked->no_of_tickets;
            $room = max(0, $capacity - $available);

            if ($room < 1) {
                return;
            }

            $locked->increment('no_of_available_tickets', min($quantity, $room));
        });
    }

    /**
     * Release the hold for a cart item (if applied) and delete it.
     */
    public function releaseAndDelete(CartItem $cartItem): void
    {
        if (Payment::cartItemHasPendingStripeCheckout((int) $cartItem->id)) {
            throw new RuntimeException(
                'This reservation is part of an in-progress payment. Complete or cancel checkout first.'
            );
        }

        DB::transaction(function () use ($cartItem) {
            $item = CartItem::query()
                ->lockForUpdate()
                ->find($cartItem->id);

            if (! $item) {
                return;
            }

            if (Payment::cartItemHasPendingStripeCheckout((int) $item->id)) {
                return;
            }

            if ($item->inventory_held) {
                $this->release((int) $item->ticket_category_id, (int) $item->quantity);
            }

            $item->delete();
        });
    }

    /**
     * Release holds and delete many cart items (e.g. event cancel / purge / expiry).
     *
     * @param  Collection<int, CartItem>|iterable<int, CartItem>  $cartItems
     */
    public function releaseAndDeleteMany(iterable $cartItems): int
    {
        $deleted = 0;

        DB::transaction(function () use ($cartItems, &$deleted) {
            $lockedIds = Payment::pendingStripeCheckoutCartItemIds();

            foreach ($cartItems as $cartItem) {
                $item = CartItem::query()
                    ->lockForUpdate()
                    ->find($cartItem->id);

                if (! $item) {
                    continue;
                }

                if (in_array((int) $item->id, $lockedIds, true)) {
                    continue;
                }

                if ($item->inventory_held) {
                    $this->release((int) $item->ticket_category_id, (int) $item->quantity);
                }

                $item->delete();
                $deleted++;
            }
        });

        return $deleted;
    }

    /**
     * Change an existing cart hold to a new quantity (reserve or release the delta).
     *
     * @throws RuntimeException when increasing beyond remaining stock
     */
    public function adjustQuantity(CartItem $cartItem, int $newQuantity): CartItem
    {
        if ($newQuantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        return DB::transaction(function () use ($cartItem, $newQuantity) {
            $item = CartItem::query()
                ->lockForUpdate()
                ->findOrFail($cartItem->id);

            if (Payment::cartItemHasPendingStripeCheckout((int) $item->id)) {
                throw new RuntimeException(
                    'This reservation is part of an in-progress payment. Complete or cancel checkout first.'
                );
            }

            $currentQuantity = (int) $item->quantity;
            $delta = $newQuantity - $currentQuantity;

            if (! $item->inventory_held) {
                $this->reserve($item->ticketCategory()->firstOrFail(), $newQuantity);
                $item->update([
                    'quantity' => $newQuantity,
                    'reserved_until' => now()->addMinutes((int) config('cart.reservation_minutes', 30)),
                    'inventory_held' => true,
                ]);

                return $item->fresh(['ticketCategory', 'event']) ?? $item;
            }

            if ($delta > 0) {
                $this->reserve($item->ticketCategory()->firstOrFail(), $delta);
            } elseif ($delta < 0) {
                $this->release((int) $item->ticket_category_id, abs($delta));
            }

            $item->update([
                'quantity' => $newQuantity,
                'reserved_until' => now()->addMinutes((int) config('cart.reservation_minutes', 30)),
            ]);

            return $item->fresh(['ticketCategory', 'event']) ?? $item;
        });
    }

    /**
     * Max quantity the user may set for a held cart line (own hold + remaining stock).
     */
    public function maxPurchasableQuantity(CartItem $cartItem): int
    {
        $available = (int) ($cartItem->ticketCategory?->no_of_available_tickets ?? 0);

        if (! $cartItem->inventory_held) {
            return max($available, (int) $cartItem->quantity);
        }

        return $available + (int) $cartItem->quantity;
    }

    /**
     * Release inventory for expired reservations and remove those cart rows.
     */
    public function releaseExpired(): int
    {
        $expired = CartItem::query()
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<=', now())
            ->get();

        $legacyExpired = CartItem::query()
            ->whereNull('reserved_until')
            ->get()
            ->filter(fn (CartItem $item) => $item->isExpired());

        $toRelease = $expired->concat($legacyExpired)->unique('id');

        return $this->releaseAndDeleteMany($toRelease);
    }

    /**
     * Release reservation-timer expired holds for one user (not booking-window expiry).
     */
    public function releaseReservationExpiredForUser(int $userId): int
    {
        $expired = CartItem::query()
            ->where('user_id', $userId)
            ->whereNotNull('reserved_until')
            ->where('reserved_until', '<=', now())
            ->get();

        $legacyExpired = CartItem::query()
            ->where('user_id', $userId)
            ->whereNull('reserved_until')
            ->get()
            ->filter(fn (CartItem $item) => $item->isExpired());

        return $this->releaseAndDeleteMany($expired->concat($legacyExpired)->unique('id'));
    }

    /**
     * Clear cart lines whose purchase deadline has passed for a user.
     */
    public function clearExpiredForUser(int $userId): int
    {
        $expired = CartItem::query()
            ->where('user_id', $userId)
            ->with('ticketCategory')
            ->get()
            ->filter(fn (CartItem $item) => $item->hasPurchaseDeadlinePassed());

        return $this->releaseAndDeleteMany($expired);
    }

    /**
     * Convert a soft-held cart item into a paid booking without double-decrementing.
     * If the item never held inventory, decrement now (legacy soft holds).
     *
     * @throws RuntimeException when legacy soft hold cannot claim stock
     */
    public function consumeHoldForPurchase(CartItem $cartItem): ticketCategory
    {
        $category = ticketCategory::query()
            ->lockForUpdate()
            ->findOrFail($cartItem->ticket_category_id);

        if ($cartItem->inventory_held) {
            return $category;
        }

        $quantity = (int) $cartItem->quantity;

        if ($quantity > (int) $category->no_of_available_tickets) {
            throw new RuntimeException(
                "Only {$category->no_of_available_tickets} ticket(s) available for {$category->name}."
            );
        }

        $category->decrement('no_of_available_tickets', $quantity);
        $category->refresh();

        $cartItem->update(['inventory_held' => true]);

        return $category;
    }

    /**
     * Apply inventory for a paid checkout line using the frozen snapshot.
     * Uses an existing cart hold when present; otherwise claims stock (paid orders still issue).
     *
     * @param  array{cart_item_id?: int, event_id: int, ticket_category_id: int, quantity: int, unit_price?: float, inventory_held?: bool}  $line
     */
    public function fulfillPaidLine(array $line, ?CartItem $cartItem): ticketCategory
    {
        $category = ticketCategory::query()
            ->lockForUpdate()
            ->findOrFail((int) $line['ticket_category_id']);

        $paidQuantity = max(0, (int) $line['quantity']);
        $heldQuantity = ($cartItem && $cartItem->inventory_held)
            ? (int) $cartItem->quantity
            : 0;

        $need = $paidQuantity - $heldQuantity;

        if ($need > 0) {
            $available = (int) $category->no_of_available_tickets;

            if ($need > $available) {
                Log::warning('Paid checkout claimed more tickets than remaining stock.', [
                    'ticket_category_id' => $category->id,
                    'paid_quantity' => $paidQuantity,
                    'held_quantity' => $heldQuantity,
                    'available' => $available,
                ]);

                if ($available > 0) {
                    $category->decrement('no_of_available_tickets', $available);
                }
            } else {
                $category->decrement('no_of_available_tickets', $need);
            }

            $category->refresh();
        } elseif ($need < 0) {
            $this->release($category, abs($need));
            $category->refresh();
        }

        return $category;
    }

    /**
     * One-time / recovery: apply hard holds for cart items that never decremented stock.
     */
    public function applyMissingHolds(): int
    {
        $applied = 0;

        CartItem::query()
            ->where('inventory_held', false)
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$applied) {
                foreach ($items as $item) {
                    DB::transaction(function () use ($item, &$applied) {
                        $lockedItem = CartItem::query()->lockForUpdate()->find($item->id);

                        if (! $lockedItem || $lockedItem->inventory_held) {
                            return;
                        }

                        $category = ticketCategory::query()
                            ->lockForUpdate()
                            ->find($lockedItem->ticket_category_id);

                        if (! $category) {
                            $lockedItem->delete();

                            return;
                        }

                        $quantity = (int) $lockedItem->quantity;
                        $available = (int) $category->no_of_available_tickets;

                        if ($quantity > $available) {
                            if ($available < 1) {
                                $lockedItem->delete();

                                return;
                            }

                            $lockedItem->update(['quantity' => $available]);
                            $quantity = $available;
                        }

                        $category->decrement('no_of_available_tickets', $quantity);
                        $lockedItem->update(['inventory_held' => true]);
                        $applied++;
                    });
                }
            });

        return $applied;
    }

    /**
     * Active cart holds still reserving stock (not expired).
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public function scopeActiveHolds($query)
    {
        $reservationMinutes = (int) config('cart.reservation_minutes', 4320);

        return $query
            ->where('inventory_held', true)
            ->where(function ($q) use ($reservationMinutes) {
                $q->where('reserved_until', '>', now())
                    ->orWhere(function ($inner) use ($reservationMinutes) {
                        $inner->whereNull('reserved_until')
                            ->where('updated_at', '>=', now()->subMinutes($reservationMinutes));
                    });
            });
    }

    /**
     * Held cart rows past reservation expiry (pending release / abandoned demand).
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public function scopeAbandonedHolds($query)
    {
        $reservationMinutes = (int) config('cart.reservation_minutes', 4320);

        return $query
            ->where('inventory_held', true)
            ->where(function ($q) use ($reservationMinutes) {
                $q->where(function ($expired) {
                    $expired->whereNotNull('reserved_until')
                        ->where('reserved_until', '<=', now());
                })->orWhere(function ($legacy) use ($reservationMinutes) {
                    $legacy->whereNull('reserved_until')
                        ->where('updated_at', '<', now()->subMinutes($reservationMinutes));
                });
            });
    }

    /**
     * Per-category cart hold snapshot for organizer inventory UI.
     *
     * @param  list<int>  $categoryIds
     * @return array<int, array{held: int, abandoned: int}>
     */
    public function holdSummaryByCategoryIds(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        $summary = [];

        foreach ($categoryIds as $id) {
            $summary[$id] = ['held' => 0, 'abandoned' => 0];
        }

        if ($categoryIds === []) {
            return $summary;
        }

        $held = CartItem::query()
            ->whereIn('ticket_category_id', $categoryIds)
            ->tap(fn ($query) => $this->scopeActiveHolds($query))
            ->selectRaw('ticket_category_id, SUM(quantity) as total')
            ->groupBy('ticket_category_id')
            ->pluck('total', 'ticket_category_id');

        $abandoned = CartItem::query()
            ->whereIn('ticket_category_id', $categoryIds)
            ->tap(fn ($query) => $this->scopeAbandonedHolds($query))
            ->selectRaw('ticket_category_id, SUM(quantity) as total')
            ->groupBy('ticket_category_id')
            ->pluck('total', 'ticket_category_id');

        foreach ($categoryIds as $id) {
            $summary[$id] = [
                'held' => (int) ($held[$id] ?? 0),
                'abandoned' => (int) ($abandoned[$id] ?? 0),
            ];
        }

        return $summary;
    }

    /**
     * @return array{held: int, abandoned: int}
     */
    public function holdSummaryForCategory(int $categoryId): array
    {
        return $this->holdSummaryByCategoryIds([$categoryId])[$categoryId]
            ?? ['held' => 0, 'abandoned' => 0];
    }
}
