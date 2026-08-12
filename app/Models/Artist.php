<?php

namespace App\Models;

use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Artist extends Model
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
        'name',
        'email',
        'contact_number',
        'cover',
        'created_by',
        'is_active',
    ];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_artist', 'artist_id', 'event_id')->withTimestamps();
    }

    public function artistLikes()
    {
        return $this->hasMany(ArtistLike::class, 'artist_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'artist_likes', 'artist_id', 'user_id')->withTimestamps();
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->artistLikes()->where('user_id', $user->id)->exists();
    }

    public function artistFollows()
    {
        return $this->hasMany(FollowArtist::class, 'artist_id');
    }

    public function followedByUsers()
    {
        return $this->belongsToMany(User::class, 'follow_artists', 'artist_id', 'user_id')->withTimestamps();
    }

    public function isFollowedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->artistFollows()->where('user_id', $user->id)->exists();
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

    public function hasLinkedEvents(): bool
    {
        return $this->events()->exists();
    }

    public function hasFollowers(): bool
    {
        return $this->artistFollows()->exists();
    }
}
