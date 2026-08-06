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
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatusEnum::class,
            'payment_method' => PaymentMethodEnum::class,
            'cart_item_ids' => 'array',
        ];
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
