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

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ComplaintResponse::class)->latest();
    }

    /**
     * Scope to complaints relevant to this CRO:
     * - event-scoped: only when they are the event's contact_person
     * - general (null event_id): unclaimed, or claimed by this CRO
     *
     * Pass $scope = 'all' to disable filtering.
     */
    public function scopeForCroQueue(Builder $query, int $croId, string $scope = 'mine'): Builder
    {
        if ($scope === 'all') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($croId) {
            $inner->where(function (Builder $general) use ($croId) {
                $general->whereNull('event_id')
                    ->where(function (Builder $claim) use ($croId) {
                        $claim->whereNull('assigned_to')
                            ->orWhere('assigned_to', $croId);
                    });
            })->orWhereIn(
                'event_id',
                Event::query()->assignedToCro($croId)->select('id')
            );
        });
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
     * General complaints have no event and are shared across CROs until claimed.
     */
    public function isGeneral(): bool
    {
        return $this->event_id === null;
    }

    public function canBeClaimed(): bool
    {
        return $this->isGeneral() && $this->isUnassigned();
    }

    public function queueOwnerName(): string
    {
        if ($this->assignee) {
            return $this->assignee->full_name;
        }

        if (! $this->isGeneral()) {
            $this->loadMissing('event.contactPerson');

            return $this->event?->contactPerson?->full_name ?? 'Event CRO';
        }

        return 'Unassigned';
    }

    /**
     * Whether this complaint is in the CRO's actionable queue.
     */
    public function isInCroQueue(int $croId): bool
    {
        if ($this->isGeneral()) {
            return $this->isUnassigned() || $this->isAssignedTo($croId);
        }

        $this->loadMissing('event');

        return (int) ($this->event?->contact_person) === $croId;
    }
}
