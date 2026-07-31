<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowHost extends Model
{
    protected $table = 'follow_hosts';

    protected $fillable = [
        'user_id',
        'host_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
