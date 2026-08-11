<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Event extends Model
{
    use Auditable, HasFactory, HasTitleCaseAttributes, Notifiable;

    /**
     * @var list<string>
     */
    protected array $titleCase = [
        'name',
        'place',
    ];

    public const STATUS_UNPUBLISHED = 'unpublished';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_POSTPONED = 'postponed';

    protected $fillable = [
        'name',
        'host_id',
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
        'postponement_reason',
        'postponed_at',
        'date_tba',
        'refunds_allowed',
        'refund_full_days_before_close',
        'refund_full_percentage',
        'refund_partial_percentage',
        'revenue_goal',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'postponed_at' => 'datetime',
            'date_tba' => 'boolean',
            'refunds_allowed' => 'boolean',
            'refund_full_days_before_close' => 'integer',
            'refund_full_percentage' => 'integer',
            'refund_partial_percentage' => 'integer',
            'revenue_goal' => 'decimal:2',
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

    public function isPostponed(): bool
    {
        return $this->status === self::STATUS_POSTPONED;
    }

    public function isPurchasedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->ticketBookings()
            ->where('user_id', $user->id)
            ->where('status', BookingStatusEnum::Confirmed)
            ->exists();
    }

    /**
     * Confirmed ticket purchased at or before the current postponement timestamp.
     */
    public function hasPrePostponementPurchaseBy(?User $user): bool
    {
        if (! $user || ! $this->isPostponed() || ! $this->postponed_at) {
            return false;
        }

        return $this->ticketBookings()
            ->where('user_id', $user->id)
            ->where('status', BookingStatusEnum::Confirmed)
            ->where('created_at', '<=', $this->postponed_at)
            ->exists();
    }

    /**
     * Status shown on public/browse surfaces.
     * Non-ticket holders (and buyers after postponement) see postponed events as upcoming.
     */
    public function publicFacingStatus(?User $user = null): string
    {
        if ($this->isPostponed() && ! $this->shouldRevealPostponementTo($user)) {
            return self::STATUS_UPCOMING;
        }

        return $this->status ?? self::STATUS_UPCOMING;
    }

    public function shouldRevealPostponementTo(?User $user = null): bool
    {
        return $this->isPostponed() && $this->hasPrePostponementPurchaseBy($user);
    }

    /**
     * Date, time, and/or place are not confirmed yet (upcoming TBA or postponed TBA).
     */
    public function hasDateYetToBeScheduled(): bool
    {
        if (! (bool) $this->date_tba) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_UPCOMING,
            self::STATUS_POSTPONED,
            self::STATUS_UNPUBLISHED,
        ], true);
    }

    /**
     * Upcoming (or unpublished) event with place/date/time still undecided.
     */
    public function isUpcomingScheduleTba(): bool
    {
        return (bool) $this->date_tba
            && in_array($this->status, [self::STATUS_UPCOMING, self::STATUS_UNPUBLISHED], true);
    }

    public function hasConfirmedSchedule(): bool
    {
        return ! $this->hasDateYetToBeScheduled()
            && filled($this->date)
            && filled($this->place);
    }

    public function displayPlace(): string
    {
        if ($this->hasDateYetToBeScheduled() || blank($this->place)) {
            return 'Place yet to be announced';
        }

        return (string) $this->place;
    }

    public function scheduleStatusLabel(): string
    {
        if ($this->hasDateYetToBeScheduled()) {
            return 'Place, date & time not decided yet';
        }

        $date = $this->formattedScheduleDate();

        if ($this->isPostponed()) {
            return $date ? 'Rescheduled to '.$date : 'Date Yet To Be Scheduled';
        }

        return $date ? 'Confirmed for '.$date : 'Schedule confirmed';
    }

    public function canBePostponed(): bool
    {
        return $this->status === self::STATUS_UPCOMING;
    }

    public function hasPassed(): bool
    {
        if ($this->hasDateYetToBeScheduled() || blank($this->date)) {
            return false;
        }

        return now()->gt(Carbon::parse($this->date)->endOfDay());
    }

    public function startsAt(): Carbon
    {
        if (blank($this->date)) {
            return now()->startOfDay();
        }

        $time = filled($this->time) ? $this->time : '00:00:00';

        return Carbon::parse($this->date.' '.$time);
    }

    public function isLocked(): bool
    {
        return $this->isCompleted() || $this->isCancelled();
    }

    public function formattedScheduleDate(?string $format = 'd M Y'): ?string
    {
        if ($this->hasDateYetToBeScheduled() || blank($this->date)) {
            return null;
        }

        return Carbon::parse($this->date)->format($format);
    }

    public function postponementScheduleLabel(): string
    {
        return $this->scheduleStatusLabel();
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

    public function scopeBookedByUser($query, int $userId)
    {
        return $query->whereHas('ticketBookings', function ($bookingQuery) use ($userId) {
            $bookingQuery
                ->where('user_id', $userId)
                ->where('status', BookingStatusEnum::Confirmed);
        });
    }

    public function calendarDisplayStatus(): string
    {
        if ($this->isCancelled()) {
            return 'cancelled';
        }

        if ($this->isCompleted()) {
            return 'completed';
        }

        if ($this->isPostponed()) {
            return 'postponed';
        }

        if ($this->status === self::STATUS_ONGOING) {
            return 'ongoing';
        }

        if ($this->status === self::STATUS_UPCOMING && $this->startsAt()->isToday()) {
            return 'ongoing';
        }

        return 'upcoming';
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
        return $this->belongsTo(Host::class, 'host_id');
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'event_artist', 'event_id', 'artist_id')->withTimestamps();
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

    public function views()
    {
        return $this->hasMany(EventView::class, 'event_id');
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

    public function postponementAlertDismissals()
    {
        return $this->hasMany(PostponementAlertDismissal::class, 'event_id');
    }
    
    public function getTotalTicketsAttribute()
    {
        if ($this->ticketCategories()->exists()) {
            return $this->ticketCategories()->sum('no_of_tickets');
        }

        return $this->no_of_tickets;
    }


} 