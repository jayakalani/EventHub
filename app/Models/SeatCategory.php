<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;

class SeatCategory extends Model
{
    use HasFactory, Notifiable, Auditable;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'no_of_seats',
        'no_of_available_seats',
        'seat_price',
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

    public function seatBookings()
    {
        return $this->hasMany(SeatBooking::class, 'seat_category_id');
    }
}
