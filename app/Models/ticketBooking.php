<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
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
        'checked_in_at',
        'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'ticket_price' => 'decimal:2',
            'status' => BookingStatusEnum::class,
            'postponement_kept_for' => 'datetime',
            'checked_in_at' => 'datetime',
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

    public function isOwnedByOrganizer(?int $organizerId): bool
    {
        $this->loadMissing('event');

        return $this->event?->isOwnedByOrganizer($organizerId) ?? false;
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

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function canCheckIn(): bool
    {
        $this->loadMissing(['event', 'refundRequest']);

        if ($this->isCheckedIn()) {
            return false;
        }

        if (! in_array($this->status, BookingStatusEnum::retainedSaleStatuses(), true)) {
            return false;
        }

        if ($this->refundRequest?->status === RefundRequestStatusEnum::Pending) {
            return false;
        }

        if ($this->event?->isCancelled()) {
            return false;
        }

        return true;
    }

    public function checkInIneligibilityReason(): ?string
    {
        $this->loadMissing(['event', 'refundRequest']);

        if ($this->isCheckedIn()) {
            return 'This ticket has already been checked in.';
        }

        if ($this->status === BookingStatusEnum::Refunded) {
            return 'This ticket has been refunded and cannot be checked in.';
        }

        if ($this->status === BookingStatusEnum::EventCancelled) {
            return 'This event was cancelled and the ticket is no longer valid.';
        }

        if ($this->status === BookingStatusEnum::BookingCancelled) {
            return 'This booking was cancelled and cannot be checked in.';
        }

        if ($this->refundRequest?->status === RefundRequestStatusEnum::Pending) {
            return 'A refund request is pending for this ticket.';
        }

        if ($this->event?->isCancelled()) {
            return 'This event is cancelled and check-in is closed.';
        }

        if (! in_array($this->status, BookingStatusEnum::retainedSaleStatuses(), true)) {
            return 'This ticket is not eligible for check-in.';
        }

        return null;
    }

    public function isExpired(): bool
    {
        $this->loadMissing('event');

        $event = $this->event;

        if (! $event) {
            return false;
        }

        if ($event->isCompleted() || $event->isPostponed()) {
            return false;
        }

        // TBA / unset dates are not past — cancellation window stays open.
        if ($event->hasDateYetToBeScheduled() || blank($event->date)) {
            return false;
        }

        try {
            return now()->gte(Carbon::parse($event->date)->startOfDay());
        } catch (\Throwable) {
            return false;
        }
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
