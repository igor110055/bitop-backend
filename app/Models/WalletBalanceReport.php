<?php

namespace App\Models;

class WalletBalanceReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'balance',
        'balance_price',
    ];
}
