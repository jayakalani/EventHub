<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class ticketCategory extends Model
{
    use Auditable, HasFactory, HasTitleCaseAttributes, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected array $titleCase = [
        'name',
    ];

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'no_of_tickets',
        'no_of_available_tickets',
        'ticket_price',
        //'discount_price',
        'ticket_color',
        'is_active',
        'booking_start',
        'booking_end',
        //'discount_start',
        //'discount_end',
    ];

    /**
     * Cast attributes to proper types.
     */
    protected $casts = [
        'is_active'     => 'boolean',
        'booking_start' => 'datetime',
        'booking_end'   => 'datetime',
        //'is_active' => 'boolean',
        //'ticket_price' => 'decimal:2',
        //'discount_price' => 'decimal:2',
        //'booking_start' => 'datetime',
        //'booking_end' => 'datetime',
        //'discount_start' => 'datetime',
        //'discount_end' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id')->withTrashed();
    }

    public function isOwnedByOrganizer(?int $organizerId): bool
    {
        $this->loadMissing('event');

        return $this->event?->isOwnedByOrganizer($organizerId) ?? false;
    }

    public function ticketBookings()
    {
        return $this->hasMany(ticketBooking::class, 'ticket_category_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'ticket_category_id');
    }

    public function hasSoldTickets(): bool
    {
        return $this->ticketBookings()
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses())
            ->exists();
    }

    /**
     * Any booking row ever recorded for this category (including cancelled/refunded).
     */
    public function hasBookingHistory(): bool
    {
        if (isset($this->ticket_bookings_count)) {
            return (int) $this->ticket_bookings_count > 0;
        }

        if ($this->relationLoaded('ticketBookings')) {
            return $this->ticketBookings->isNotEmpty();
        }

        return $this->ticketBookings()->exists();
    }

    public function isSalesOpenNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->booking_start && $this->booking_start->isFuture()) {
            return false;
        }

        if ($this->booking_end && $this->booking_end->isPast()) {
            return false;
        }

        return (int) $this->no_of_available_tickets > 0;
    }
}
