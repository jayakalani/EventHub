<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'purpose',
        'cart_item_ids',
        'checkout_items',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatusEnum::class,
            'payment_method' => PaymentMethodEnum::class,
            'cart_item_ids' => 'array',
            'checkout_items' => 'array',
        ];
    }

    /**
     * Cart item IDs currently reserved by an in-progress Stripe ticket checkout.
     *
     * @return list<int>
     */
    public static function pendingStripeCheckoutCartItemIds(): array
    {
        return static::query()
            ->where('status', PaymentStatusEnum::Pending)
            ->where('purpose', 'ticket_purchase')
            ->where('payment_method', PaymentMethodEnum::Stripe)
            ->get(['cart_item_ids', 'checkout_items'])
            ->flatMap(function (self $payment) {
                $fromSnapshot = collect($payment->checkout_items ?? [])->pluck('cart_item_id');

                return $fromSnapshot->concat($payment->cart_item_ids ?? []);
            })
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function cartItemHasPendingStripeCheckout(int $cartItemId): bool
    {
        return in_array($cartItemId, static::pendingStripeCheckoutCartItemIds(), true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticketBookings(): HasMany
    {
        return $this->hasMany(ticketBooking::class);
    }

    public function isOwnedByOrganizer(?int $organizerId): bool
    {
        if ($organizerId === null) {
            return false;
        }

        return $this->ticketBookings()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->exists();
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatusEnum::Completed;
    }
}
