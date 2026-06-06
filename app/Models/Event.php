<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;

class Event extends Model
{
    use HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'name',
        'hosted_by',
        'category_id',
        'date',
        'time',
        'place',
        'no_of_seats',
        'description',
        'contact_person',
        'cover',
        'created_by',
    ];

    public function host()
    {
        return $this->belongsTo(Host::class, 'hosted_by');
    }

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function contactPerson()
    {
        return $this->belongsTo(User::class, 'contact_person');
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seatCategories()
    {
        return $this->hasMany(SeatCategory::class,'event_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'user_id');
    }

    public function seatBookings()
    {
        return $this->hasMany(SeatBooking::class,'event_id');
    }
}
