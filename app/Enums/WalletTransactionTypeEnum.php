<?php

namespace App\Enums;

enum WalletTransactionTypeEnum: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
