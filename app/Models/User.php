<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\GenderEnum;
use App\Traits\Auditable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Auditable, HasFactory, MustVerifyEmail, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'nic',
        'email',
        'google_id',
        'contact_number',
        'date_of_birth',
        'address',
        'gender',
        'role_id',
        'profile_photo',
        'password',
        'failed_attempts',
        'is_locked',
        'created_by',
        'is_active',
        'is_default_password_changed',
        'profile_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the user's full name by concatenating first and last name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'is_locked' => 'boolean',
            'is_active' => 'boolean',
            'is_default_password_changed' => 'boolean',
            'profile_completed' => 'boolean',
            'gender' => GenderEnum::class,
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Get the role that belongs to the user.
     */
    public function userRole(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function likedEvents()
    {
        return $this->belongsToMany(Event::class, 'likes', 'user_id', 'event_id')->withTimestamps();
    }

    public function hasLiked(Event $event): bool
    {
        return $this->likes()->where('event_id', $event->id)->exists();
    }

    public function hostLikes()
    {
        return $this->hasMany(HostLike::class);
    }

    public function likedHosts()
    {
        return $this->belongsToMany(Host::class, 'host_likes', 'user_id', 'host_id')->withTimestamps();
    }

    public function hasLikedHost(Host $host): bool
    {
        return $this->hostLikes()->where('host_id', $host->id)->exists();
    }

    public function savedEvents()
    {
        return $this->belongsToMany(Event::class, 'saved_events', 'user_id', 'event_id')->withTimestamps();
    }

    public function hasSaved(Event $event): bool
    {
        return $this->savedEvents()->where('event_id', $event->id)->exists();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function ratedEvents()
    {
        return $this->belongsToMany(Event::class, 'ratings', 'user_id', 'event_id')->withTimestamps();
    }

    public function ratingFor(Event $event): ?int
    {
        return $this->ratings()->where('event_id', $event->id)->value('score');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function ticketBookings()
    {
        return $this->hasMany(ticketBooking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
