<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;

class Event extends Model
{
    use HasFactory, Notifiable, Auditable;

    public const STATUS_UNPUBLISHED = 'unpublished';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'hosted_by',
        'category_id',
        'date',
        'time',
        'place',
        'no_of_tickets',
        'description',
        'contact_person',
        'cover',
        'created_by',
        'status',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }

    public function scopeVisibleToAttendees($query)
    {
        return $query->where('status', '!=', self::STATUS_UNPUBLISHED);
    }

    public function isVisibleToAttendees(): bool
    {
        return $this->status !== self::STATUS_UNPUBLISHED;
    }

    public function ensureVisibleToAttendees(): void
    {
        if (! $this->isVisibleToAttendees()) {
            abort(404);
        }
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function hasPassed(): bool
    {
        return now()->gt(Carbon::parse($this->date)->endOfDay());
    }

    public function isLocked(): bool
    {
        return $this->isCompleted() || $this->isCancelled();
    }

    public function scopeActiveForAttendees($query)
    {
        return $query
            ->visibleToAttendees()
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopePastForAttendees($query)
    {
        return $query
            ->visibleToAttendees()
            ->where('status', self::STATUS_COMPLETED);
    }

    public function ensureViewable(): void
    {
        $this->ensureVisibleToAttendees();
    }

    public function ensureBookable(): void
    {
        $this->ensureVisibleToAttendees();

        if ($this->isCancelled()) {
            abort(403, 'This event has been cancelled and is no longer available for booking.');
        }

        if ($this->isCompleted()) {
            abort(403, 'This event has ended and is no longer available for booking.');
        }
    }

    public function ensureFeedbackAllowed(): void
    {
        $this->ensureVisibleToAttendees();

        if ($this->isCancelled()) {
            abort(403, 'This event has been cancelled and is no longer available for interaction.');
        }
    }

    public function ensureInteractive(): void
    {
        $this->ensureBookable();
    }

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

    public function scopeCreatedByOrganizer($query, int $organizerId)
    {
        return $query->where('created_by', $organizerId);
    }

    public function isOwnedByOrganizer(?int $organizerId): bool
    {
        return $organizerId !== null && (int) $this->created_by === (int) $organizerId;
    }

    public function ticketCategories()
    {
        return $this->hasMany(ticketCategory::class,'event_id');
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

    public function ticketBookings()
    {
        return $this->hasMany(ticketBooking::class,'event_id');
    }

    public function hasSoldTickets(): bool
    {
        return $this->ticketBookings()->exists();
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'event_id');
    }
    
    public function getTotalTicketsAttribute()
    {
        if ($this->ticketCategories()->exists()) {
            return $this->ticketCategories()->sum('no_of_tickets');
        }

        return $this->no_of_tickets;
    }


} 