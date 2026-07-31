<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ticketBooking extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'ticket_category_id',
        'payment_id',
        'ticket_number',
        'ticket_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ticket_price' => 'decimal:2',
            'status' => BookingStatusEnum::class,
        ];
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function refundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class, 'ticket_booking_id');
    }

    public function isExpired(): bool
    {
        $this->loadMissing('event');

        if ($this->event->isCompleted()) {
            return false;
        }

        return now()->gte(Carbon::parse($this->event->date)->startOfDay());
    }

    public function isCancellable(): bool
    {
        $this->loadMissing('event');

        return $this->status === BookingStatusEnum::Confirmed
            && $this->refundRequest === null
            && ! $this->isExpired()
            && ! $this->event->isCompleted()
            && $this->event->refunds_allowed;
    }

    public function displayStatusLabel(): string
    {
        $this->loadMissing('event');

        if ($this->status === BookingStatusEnum::Confirmed && $this->event->isCompleted()) {
            return 'Completed';
        }

        return ucfirst(str_replace('_', ' ', $this->status->value));
    }

    public function displayStatusBadgeClasses(): string
    {
        $this->loadMissing('event');

        if ($this->status === BookingStatusEnum::Confirmed && $this->event->isCompleted()) {
            return 'bg-slate-200 text-slate-700';
        }

        if ($this->status === BookingStatusEnum::Confirmed) {
            return 'bg-emerald-100 text-emerald-700';
        }

        if ($this->status === BookingStatusEnum::EventCancelled) {
            return 'bg-rose-100 text-rose-700';
        }

        return 'bg-slate-100 text-slate-700';
    }
}
