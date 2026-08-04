<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostponementAlertDismissal extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'postponed_at',
    ];

    protected function casts(): array
    {
        return [
            'postponed_at' => 'datetime',
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
}
