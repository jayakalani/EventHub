<?php

namespace App\Models;

use App\Enums\RefundRequestStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    protected $fillable = [
        'ticket_booking_id',
        'user_id',
        'reason',
        'refund_percentage',
        'refund_amount',
        'status',
        'reviewed_by',
        'reviewed_at',
        'cro_notes',
    ];

    protected function casts(): array
    {
        return [
            'refund_percentage' => 'integer',
            'refund_amount' => 'decimal:2',
            'status' => RefundRequestStatusEnum::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function ticketBooking(): BelongsTo
    {
        return $this->belongsTo(ticketBooking::class, 'ticket_booking_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === RefundRequestStatusEnum::Pending;
    }
}
