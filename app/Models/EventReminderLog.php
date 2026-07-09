<?php

namespace App\Models;

use App\Enums\EventReminderTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReminderLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'user_id',
        'reminder_type',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_type' => EventReminderTypeEnum::class,
            'sent_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
