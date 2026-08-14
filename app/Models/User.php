<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\GenderEnum;
use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Auditable, HasFactory, HasTitleCaseAttributes, MustVerifyEmail, Notifiable, SoftDeletes;

    /**
     * Human-readable name fields that should be title-cased on save.
     *
     * @var list<string>
     */
    protected array $titleCase = [
        'first_name',
        'last_name',
        'address',
    ];

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
        'monthly_revenue_goal',
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
            'monthly_revenue_goal' => 'decimal:2',
            'gender' => GenderEnum::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            // Soft delete only — free unique columns so the same email/NIC can be reused.
            if ($user->isForceDeleting()) {
                return;
            }

            $user->forceFill([
                'email' => self::releaseUniqueValue($user->id, $user->email, 'email'),
                'nic' => self::releaseUniqueValue($user->id, $user->nic, 'nic'),
                'google_id' => null,
                'email_verified_at' => null,
                'is_active' => false,
            ])->saveQuietly();
        });
    }

    /**
     * Prefix a unique value so soft-deleted rows no longer collide with new accounts.
     */
    private static function releaseUniqueValue(int $id, ?string $value, string $fallback): string
    {
        $prefix = "deleted.{$id}.";
        $base = $value ?: $fallback;

        return $prefix.substr($base, 0, max(0, 255 - strlen($prefix)));
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

    public function isOrganizer(): bool
    {
        $this->loadMissing('userRole');

        return $this->userRole?->name_en === UserRole::ORGANIZER;
    }

    public function isAdmin(): bool
    {
        $this->loadMissing('userRole');

        return $this->userRole?->name_en === UserRole::ADMIN;
    }

    /**
     * Admin who can currently sign in and manage the platform.
     */
    public function isOperationalAdmin(): bool
    {
        return $this->isAdmin()
            && (bool) $this->is_active
            && ! (bool) $this->is_locked;
    }

    public function isCurrentAuthUser(): bool
    {
        $actorId = Auth::id();

        return $actorId !== null && (int) $actorId === (int) $this->id;
    }

    /**
     * @return Builder<User>
     */
    public static function operationalAdminsQuery(): Builder
    {
        return static::query()
            ->where('is_active', true)
            ->where('is_locked', false)
            ->whereHas('userRole', fn (Builder $query) => $query->where('name_en', UserRole::ADMIN));
    }

    public static function operationalAdminCount(): int
    {
        return static::operationalAdminsQuery()->count();
    }

    public function isSoleOperationalAdmin(): bool
    {
        return $this->isOperationalAdmin() && static::operationalAdminCount() <= 1;
    }

    /**
     * Whether destructive account actions should be hidden for this row.
     * Unlock / activate remain available for other admins when the account is already impaired.
     */
    public function shouldHideDestructiveAdminActions(): bool
    {
        if ($this->isCurrentAuthUser()) {
            return true;
        }

        return $this->isSoleOperationalAdmin();
    }

    /**
     * Server-side guard message for lock / deactivate / delete / demote.
     * Unlocking or reactivating is always allowed.
     *
     * @param  'lock'|'deactivate'|'delete'|'demote'  $action
     */
    public function adminProtectionError(string $action): ?string
    {
        $isSelf = $this->isCurrentAuthUser();

        return match ($action) {
            'lock' => $this->protectionErrorForImpairingAction(
                $isSelf,
                impairing: ! $this->is_locked,
                selfMessage: 'You cannot lock your own account.',
                lastAdminMessage: 'Cannot lock the last active admin account.',
            ),
            'deactivate' => $this->protectionErrorForImpairingAction(
                $isSelf,
                impairing: (bool) $this->is_active,
                selfMessage: 'You cannot deactivate your own account.',
                lastAdminMessage: 'Cannot deactivate the last active admin account.',
            ),
            'delete' => $this->protectionErrorForImpairingAction(
                $isSelf,
                impairing: true,
                selfMessage: 'You cannot delete your own account.',
                lastAdminMessage: 'Cannot delete the last active admin account.',
            ),
            'demote' => $this->protectionErrorForImpairingAction(
                $isSelf,
                impairing: $this->isAdmin(),
                selfMessage: 'You cannot change your own admin role.',
                lastAdminMessage: 'Cannot demote the last active admin account.',
            ),
            default => null,
        };
    }

    private function protectionErrorForImpairingAction(
        bool $isSelf,
        bool $impairing,
        string $selfMessage,
        string $lastAdminMessage,
    ): ?string {
        if (! $impairing) {
            return null;
        }

        if ($isSelf) {
            return $selfMessage;
        }

        if ($this->isSoleOperationalAdmin()) {
            return $lastAdminMessage;
        }

        return null;
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function revenueGoals()
    {
        return $this->hasMany(OrganizerRevenueGoal::class)->latest('starts_at');
    }

    public function likedEvents()
    {
        return $this->belongsToMany(Event::class, 'likes', 'user_id', 'event_id')
            ->withTrashed()
            ->withTimestamps();
    }

    public function hasLiked(Event $event): bool
    {
        return $this->likes()->where('event_id', $event->id)->exists();
    }

    public function artistLikes()
    {
        return $this->hasMany(ArtistLike::class);
    }

    public function likedArtists()
    {
        return $this->belongsToMany(Artist::class, 'artist_likes', 'user_id', 'artist_id')->withTimestamps();
    }

    public function hasLikedArtist(Artist $artist): bool
    {
        return $this->artistLikes()->where('artist_id', $artist->id)->exists();
    }

    public function artistFollows()
    {
        return $this->hasMany(FollowArtist::class);
    }

    public function followedArtists()
    {
        return $this->belongsToMany(Artist::class, 'follow_artists', 'user_id', 'artist_id')->withTimestamps();
    }

    public function hasFollowedArtist(Artist $artist): bool
    {
        return $this->artistFollows()->where('artist_id', $artist->id)->exists();
    }

    public function savedEvents()
    {
        return $this->belongsToMany(Event::class, 'saved_events', 'user_id', 'event_id')
            ->withTrashed()
            ->withTimestamps();
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
        return $this->belongsToMany(Event::class, 'ratings', 'user_id', 'event_id')
            ->withTrashed()
            ->withTimestamps();
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

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class);
    }
}
