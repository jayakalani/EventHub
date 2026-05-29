<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SeatCategory extends Model
{
    use HasFactory;

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


    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function seatBookings()
    {
        return $this->hasMany(SeatBooking::class, 'seat_category_id');
    }

    
}
