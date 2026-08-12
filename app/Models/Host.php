<?php

namespace App\Models;

use App\Models\Concerns\HasTitleCaseAttributes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Host extends Model
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
        return $this->hasMany(Event::class, 'host_id');
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
}
