<?php

namespace App\Models;

use App\Enums\SupportTicketStatusEnum;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'event_id',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InquiryResponse::class)->latest();
    }

    /**
     * Scope to events where this CRO is the assigned contact person.
     * Pass $scope = 'all' to disable filtering (admin-style views).
     */
    public function scopeForCroQueue(Builder $query, int $croId, string $scope = 'mine'): Builder
    {
        if ($scope === 'all') {
            return $query;
        }

        return $query->whereIn(
            'event_id',
            Event::query()->assignedToCro($croId)->select('id')
        );
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

    public function canBeClaimed(): bool
    {
        return false;
    }

    public function queueOwnerName(): string
    {
        if ($this->assignee) {
            return $this->assignee->full_name;
        }

        $this->loadMissing('event.contactPerson');

        return $this->event?->contactPerson?->full_name ?? 'Event CRO';
    }

    /**
     * Whether this inquiry is in the CRO's actionable queue.
     */
    public function isInCroQueue(int $croId): bool
    {
        if ($this->event_id === null) {
            return false;
        }

        $this->loadMissing('event');

        return (int) ($this->event?->contact_person) === $croId;
    }
}
