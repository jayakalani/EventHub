<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'ticket_category_id',
        'quantity',
        'reserved_until',
        'inventory_held',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_until' => 'datetime',
            'inventory_held' => 'boolean',
        ];
    }

    public function expiresAt(): Carbon
    {
        if ($this->reserved_until) {
            return $this->reserved_until;
        }

        $minutes = (int) config('cart.reservation_minutes', 30);

        return $this->updated_at->copy()->addMinutes($minutes);
    }

    public function isExpired(): bool
    {
        return now()->gte($this->expiresAt());
    }

    /**
     * Earliest moment the item can no longer be purchased:
     * reservation hold expiry or ticket category booking end.
     */
    public function purchaseDeadlineAt(): Carbon
    {
        $deadline = $this->expiresAt();

        $bookingEnd = $this->ticketCategory?->booking_end;
        if ($bookingEnd && $bookingEnd->lt($deadline)) {
            $deadline = $bookingEnd->copy();
        }

        return $deadline;
    }

    public function hasPurchaseDeadlinePassed(): bool
    {
        return now()->gte($this->purchaseDeadlineAt());
    }

    public function minutesUntilExpiry(): int
    {
        return max(0, (int) now()->diffInMinutes($this->expiresAt(), false));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(ticketCategory::class, 'ticket_category_id');
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) $this->ticketCategory->ticket_price;
    }

    public function getLineTotalAttribute(): float
    {
        return $this->unit_price * $this->quantity;
    }
}
