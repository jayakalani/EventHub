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
        'postponement_kept_for',
    ];

    protected function casts(): array
    {
        return [
            'ticket_price' => 'decimal:2',
            'status' => BookingStatusEnum::class,
            'postponement_kept_for' => 'datetime',
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

        if ($this->event->isPostponed()) {
            return false;
        }

        return now()->gte(Carbon::parse($this->event->date)->startOfDay());
    }

    public function isCancellable(): bool
    {
        $this->loadMissing('event');

        // Pre-postponement tickets use Keep / Full Refund instead of the normal cancel flow.
        if ($this->event->isPostponed() && $this->wasPurchasedBeforeCurrentPostponement()) {
            return false;
        }

        return $this->status === BookingStatusEnum::Confirmed
            && $this->refundRequest === null
            && ! $this->isExpired()
            && ! $this->event->isCompleted()
            && $this->event->refunds_allowed;
    }

    /**
     * Ticket existed when the event was postponed (buyer is affected by postponement).
     */
    public function wasPurchasedBeforeCurrentPostponement(): bool
    {
        $this->loadMissing('event');

        if (! $this->event?->isPostponed() || ! $this->event->postponed_at || ! $this->created_at) {
            return false;
        }

        return $this->created_at->lte($this->event->postponed_at);
    }

    public function isPostponementRefundable(): bool
    {
        $this->loadMissing(['event', 'refundRequest']);

        return $this->status === BookingStatusEnum::Confirmed
            && $this->refundRequest === null
            && $this->wasPurchasedBeforeCurrentPostponement()
            && ! $this->hasKeptCurrentPostponement();
    }

    public function hasKeptCurrentPostponement(): bool
    {
        $this->loadMissing('event');

        if (! $this->wasPurchasedBeforeCurrentPostponement() || ! $this->postponement_kept_for) {
            return false;
        }

        return $this->postponement_kept_for->equalTo($this->event->postponed_at);
    }

    public function displayStatusLabel(): string
    {
        $this->loadMissing('event');

        if ($this->status === BookingStatusEnum::Confirmed && $this->event->isCompleted()) {
            return 'Completed';
        }

        if ($this->status === BookingStatusEnum::Confirmed && $this->wasPurchasedBeforeCurrentPostponement()) {
            return 'Postponed';
        }

        return ucfirst(str_replace('_', ' ', $this->status->value));
    }

    public function displayStatusBadgeClasses(): string
    {
        $this->loadMissing('event');

        if ($this->status === BookingStatusEnum::Confirmed && $this->event->isCompleted()) {
            return 'bg-slate-200 text-slate-700';
        }

        if ($this->status === BookingStatusEnum::Confirmed && $this->wasPurchasedBeforeCurrentPostponement()) {
            return 'bg-amber-100 text-amber-800';
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
