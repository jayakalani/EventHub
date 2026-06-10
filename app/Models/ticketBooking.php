<?php

namespace App\Models;

use App\Enums\BookingStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ticketBooking extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'ticket_category_id',
        'payment_id',
        'ticket_number',
        'ticket_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ticket_price' => 'decimal:2',
            'status' => BookingStatusEnum::class,
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

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(ticketCategory::class, 'ticket_category_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
