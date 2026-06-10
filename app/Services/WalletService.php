<?php

namespace App\Services;

use App\Enums\WalletTransactionTypeEnum;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
    }

    public function credit(
        User $user,
        float $amount,
        string $description,
        ?Model $source = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $source) {
            $wallet = Wallet::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0]
                );

            $wallet->increment('balance', $amount);
            $wallet->refresh();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransactionTypeEnum::Credit,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);
        });
    }

    public function debit(
        User $user,
        float $amount,
        string $description,
        ?Model $source = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $source) {
            $wallet = Wallet::query()
                ->lockForUpdate()
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ((float) $wallet->balance < $amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $wallet->decrement('balance', $amount);
            $wallet->refresh();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransactionTypeEnum::Debit,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);
        });
    }

    public function creditRefund(RefundRequest $refundRequest): WalletTransaction
    {
        $refundRequest->loadMissing('user', 'ticketBooking');

        return $this->credit(
            $refundRequest->user,
            (float) $refundRequest->refund_amount,
            'Refund for ticket '.$refundRequest->ticketBooking->ticket_number,
            $refundRequest,
        );
    }
}
