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
        return $this->belongsTo(Event::class);
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
     * Scope to the CRO's assigned events (contact_person only).
     * Pass $scope = 'all' to disable filtering (admin-style views).
     */
    public function scopeForCroQueue(Builder $query, int $croId, string $scope = 'mine'): Builder
    {
        if ($scope === 'all') {
            return $query;
        }

        return $query->whereHas('event', fn (Builder $event) => $event->where('contact_person', $croId));
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
     * Whether this inquiry belongs to an event assigned to the CRO.
     */
    public function isInCroQueue(int $croId): bool
    {
        $this->loadMissing('event');

        return (int) ($this->event?->contact_person) === $croId;
    }
}
