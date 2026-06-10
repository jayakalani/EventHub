<?php

namespace App\Models;

use App\Enums\WalletTransactionTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => WalletTransactionTypeEnum::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
