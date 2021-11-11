<?php
namespace App\Models;

class Transaction extends UuidModel
{
    const TYPE_ACTIVATE_ADVERTISEMENT = 'activate-advertisement';
    const TYPE_DEACTIVATE_ADVERTISEMENT = 'deactivate-advertisement';
    const TYPE_MATCH_ADVERTISEMENT = 'match-advertisement';
    const TYPE_CREATE_ORDER = 'create-order';
    const TYPE_COMPLETE_ORDER = 'complete-order';
    const TYPE_CANCEL_ORDER = 'cancel-order';
    const TYPE_SELL_ORDER = 'sell-order';
    const TYPE_BUY_ORDER = 'buy-order';
    const TYPE_ORDER_FEE = 'order-fee';
    const TYPE_TRANSFER_LOCK = 'transfer-lock';
    const TYPE_TRANSFER_UNLOCK = 'transfer-unlock';
    const TYPE_TRANSFER_CANCELED = 'transfer-canceled';
    const TYPE_TRANSFER_IN = 'transfer-in';
    const TYPE_TRANSFER_OUT = 'transfer-out';
    const TYPE_FEE_SHARE = 'fee-share';
    const TYPE_MANUAL_DEPOSIT = 'manual-deposit';
    const TYPE_MANUAL_WITHDRAWAL = 'manual-withdrawal';
    const TYPE_WALLET_DEPOSIT = 'wallet-deposit';
    const TYPE_WALLET_WITHDRAWAL = 'wallet-withdrawal';
    const TYPE_WALLET_WITHDRAWAL_LOCK = 'wallet-withdrawal-lock';
    const TYPE_WALLET_WITHDRAWAL_UNLOCK = 'wallet-withdrawal-unlock';
    const TYPE_WALLET_WITHDRAWAL_CANCELED = 'wallet-withdrawal-canceled';
    const TYPE_WITHDRAWAL_FEE = 'withdrawal-fee';
    const TYPES = [
        self::TYPE_ACTIVATE_ADVERTISEMENT,
        self::TYPE_DEACTIVATE_ADVERTISEMENT,
        self::TYPE_MATCH_ADVERTISEMENT,
        self::TYPE_CREATE_ORDER,
        self::TYPE_COMPLETE_ORDER,
        self::TYPE_CANCEL_ORDER,
        self::TYPE_SELL_ORDER,
        self::TYPE_BUY_ORDER,
        self::TYPE_ORDER_FEE,
        self::TYPE_TRANSFER_LOCK,
        self::TYPE_TRANSFER_UNLOCK,
        self::TYPE_TRANSFER_CANCELED,
        self::TYPE_TRANSFER_IN,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_FEE_SHARE,
        self::TYPE_MANUAL_DEPOSIT,
        self::TYPE_MANUAL_WITHDRAWAL,
        self::TYPE_WALLET_DEPOSIT,
        self::TYPE_WALLET_WITHDRAWAL,
        self::TYPE_WALLET_WITHDRAWAL_LOCK,
        self::TYPE_WALLET_WITHDRAWAL_UNLOCK,
        self::TYPE_WALLET_WITHDRAWAL_CANCELED,
        self::TYPE_WITHDRAWAL_FEE,
    ];
    const ORDER_TYPES = [
        self::TYPE_CREATE_ORDER,
        self::TYPE_COMPLETE_ORDER,
        self::TYPE_CANCEL_ORDER,
        self::TYPE_SELL_ORDER,
        self::TYPE_BUY_ORDER,
        self::TYPE_ORDER_FEE,
        self::TYPE_FEE_SHARE,
    ];
    const MANUAL_TYPES = [
        self::TYPE_MANUAL_DEPOSIT,
        self::TYPE_MANUAL_WITHDRAWAL,
    ];
    const WALLET_TYPES = [
        self::TYPE_WALLET_DEPOSIT,
        self::TYPE_WALLET_WITHDRAWAL,
        self::TYPE_WALLET_WITHDRAWAL_LOCK,
        self::TYPE_WALLET_WITHDRAWAL_UNLOCK,
        self::TYPE_WALLET_WITHDRAWAL_CANCELED,
        self::TYPE_WITHDRAWAL_FEE,
    ];

    protected $fillable = [
        'account_id',
        'coin',
        'type',
        'amount',
        'balance',
        'unit_price',
        'result_unit_price',
        'is_locked',
        'message',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transactable()
    {
        return $this->morphTo();
    }

    # scopes
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($query) use ($keyword) {
            $like = "%{$keyword}%";
            return $query
                ->orWhere('type', 'like', $like)
                ->orWhere('amount', 'like', $like)
                ->orWhere('balance', 'like', $like);
        });
    }
}
