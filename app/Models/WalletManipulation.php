<?php

namespace App\Models;

class WalletManipulation extends UuidModel
{
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';

    const TYPES = [
        self::TYPE_DEPOSIT,
        self::TYPE_WITHDRAWAL,
    ];

    protected $fillable = [
        'coin',
        'type',
        'wallet_id',
        'transaction',
        'amount',
        'response',
        'callback_response',
    ];

    protected $casts = [
        'response' => 'array',
        'callback_response' => 'array',
    ];

    public function wallet_balance_logs()
    {
        return $this->morphMany(WalletBalanceLog::class, 'wlogable');
    }
}
