<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ticketCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * Return held tickets to available stock.
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

            $locked->increment('no_of_available_tickets', $quantity);
        });
    }

    /**
     * Release the hold for a cart item (if applied) and delete it.
     */
    public function releaseAndDelete(CartItem $cartItem): void
    {
        DB::transaction(function () use ($cartItem) {
            $item = CartItem::query()
                ->lockForUpdate()
                ->find($cartItem->id);

            if (! $item) {
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
            foreach ($cartItems as $cartItem) {
                $item = CartItem::query()
                    ->lockForUpdate()
                    ->find($cartItem->id);

                if (! $item) {
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
}
