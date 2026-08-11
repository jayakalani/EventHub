<?php

namespace App\Models;

use App\Enums\SupportTicketStatusEnum;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'status',
        'assigned_to',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatusEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ComplaintResponse::class)->latest();
    }

    /**
     * Scope to complainants who booked events where this CRO is contact_person.
     * Pass $scope = 'all' to disable filtering.
     */
    public function scopeForCroQueue(Builder $query, int $croId, string $scope = 'mine'): Builder
    {
        if ($scope === 'all') {
            return $query;
        }

        return $query->whereIn('user_id', ticketBooking::query()
            ->whereIn('event_id', Event::query()->where('contact_person', $croId)->select('id'))
            ->select('user_id'));
    }

    public function scopeAssignmentFilter(Builder $query, string $assignment, int $croId): Builder
    {
        return match ($assignment) {
            'unassigned' => $query->whereNull('assigned_to'),
            'me' => $query->where('assigned_to', $croId),
            default => $query,
        };
    }

    public function isAssignedTo(?int $userId): bool
    {
        return $userId !== null && (int) $this->assigned_to === (int) $userId;
    }

    public function isUnassigned(): bool
    {
        return $this->assigned_to === null;
    }

    /**
     * Whether this complaint belongs to an attendee of the CRO's assigned events.
     */
    public function isInCroQueue(int $croId): bool
    {
        return ticketBooking::query()
            ->where('user_id', $this->user_id)
            ->whereHas('event', fn (Builder $event) => $event->where('contact_person', $croId))
            ->exists();
    }
}
