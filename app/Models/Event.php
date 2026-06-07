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
        return $this->hasMany(Comment::class, 'event_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'event_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'likes', 'event_id', 'user_id')->withTimestamps();
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function saves()
    {
        return $this->hasMany(SavedEvent::class, 'event_id');
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'saved_events', 'event_id', 'user_id')->withTimestamps();
    }

    public function isSavedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->saves()->where('user_id', $user->id)->exists();
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'event_id');
    }

    public function ratedByUsers()
    {
        return $this->belongsToMany(User::class, 'ratings', 'event_id', 'user_id')->withTimestamps();
    }

    public function userRating(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        return $this->ratings()->where('user_id', $user->id)->value('score');
    }

    public function seatBookings()
    {
        return $this->hasMany(SeatBooking::class,'event_id');
    }
}
