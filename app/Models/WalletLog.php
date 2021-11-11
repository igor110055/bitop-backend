<?php

namespace App\Models;

class WalletLog extends UuidModel
{
    const TYPE_PAYIN = 'payin';
    const TYPE_PAYOUT= 'payout';
    const TYPE_APPROVEMENT = 'approvement';

    const TYPES = [
        self::TYPE_PAYIN,
        self::TYPE_PAYOUT,
        self::TYPE_APPROVEMENT,
    ];

    protected $fillable = [
        'coin',
        'type',
        'wallet_id',
        'address',
        'fee',
        'callback_response',
    ];

    protected $casts = [
        'callback_response' => 'array',
    ];

    public function wallet_balance_logs()
    {
        return $this->morphMany(WalletBalanceLog::class, 'wlogable');
    }
}
