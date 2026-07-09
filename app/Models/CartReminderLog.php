<?php

namespace App\Models;

use App\Enums\CartReminderTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartReminderLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cart_item_id',
        'user_id',
        'reminder_type',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_type' => CartReminderTypeEnum::class,
            'sent_at' => 'datetime',
        ];
    }

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
