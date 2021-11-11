<?php

namespace App\Models;

class CheckWalletBalanceLog extends UuidModel
{
    protected $fillable = [
        'coin',
        'system_balance',
        'balance',
        'free_balance',
        'addresses_balance',
        'addresses_free_balance',
        'change_balance',
    ];
}
