<?php

namespace App\Models;

class WalletBalanceLog extends UuidModel
{
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_WALLET_FEE = 'wallet-fee';
    const TYPE_WALLET_FEE_CORRECTION = 'wallet-fee-correction';
    const TYPE_MANUAL_CORRECTION = 'manual-correction';
    const TYPE_MANUAL_DEPOSIT = 'manual-deposit';
    const TYPE_MANUAL_WITHDRAWAL = 'manual-withdrawal';
    const TYPE_PAYIN = 'payin';
    const TYPE_PAYOUT = 'payout';
    const TYPE_APPROVEMENT = 'approvement';

    const TYPES =[
        self::TYPE_DEPOSIT,
        self::TYPE_WITHDRAWAL,
        self::TYPE_WALLET_FEE,
        self::TYPE_WALLET_FEE_CORRECTION,
        self::TYPE_MANUAL_CORRECTION,
        self::TYPE_MANUAL_DEPOSIT,
        self::TYPE_MANUAL_WITHDRAWAL,
        self::TYPE_PAYIN,
        self::TYPE_PAYOUT,
        self::TYPE_APPROVEMENT,
    ];

    protected $fillable = [
        'wallet_balance_id',
        'coin',
        'type',
        'amount',
        'balance',
    ];

    public function wlogable()
    {
        return $this->morphTo();
    }
}
