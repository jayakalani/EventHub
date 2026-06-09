<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Traits\Auditable;

class Host extends Model
{
    use HasFactory, Notifiable, Auditable;

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
        return $this->hasMany(Event::class,'hosted_by');
    }

    public function hostLikes()
    {
        return $this->hasMany(HostLike::class, 'host_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'host_likes', 'host_id', 'user_id')->withTimestamps();
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->hostLikes()->where('user_id', $user->id)->exists();
    }
}
