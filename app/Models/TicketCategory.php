<?php

namespace App\Models;

use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class ticketCategory extends Model
{
    use Auditable, HasFactory, HasTitleCaseAttributes, Notifiable;

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
        'ticket_color',
        'is_active',
        'booking_start',
        'booking_end',
    ];

    /**
     * Cast attributes to proper types.
     */
    protected $casts = [
        'is_active'     => 'boolean',
        'booking_start' => 'datetime',
        'booking_end'   => 'datetime',
    ];

    /**
     * Relationships
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
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
