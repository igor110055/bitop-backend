<?php

namespace App\Models;

class ExportLog extends Model
{
    const COIN_TYPES = [
        'USDT-TRC20' => 'F',
        'USDT-ERC20' => 'V',
        'BTC' => 'B',
        'ETH' => 'E',
        'TRX' => 'X',
    ];

    const ORDER_SELL_ACCOUNTS = [
        'USDT-TRC20' => 'sellouttrc',
        'USDT-ERC20' => 'sellouterc',
        'BTC' => 'selloutbtc',
        'ETH' => 'sellouteth',
        'TRX' => 'sellouttrx',
    ];

    const MEMBER_ACCOUNTS = [
        'USDT-TRC20' => 'memberTRC',
        'USDT-ERC20' => 'memberERC',
        'BTC' => 'memberBTC',
        'ETH' => 'memberETH',
        'TRX' => 'memberTRX',
    ];

    protected $fillable = [
        'user_id',
        'transaction_id',
        'account',
        'amount',
        'coin',
        'bank_fee',
        'system_fee',
        'c_fee',
        'type',
        'bankc_fee',
        'handler_id',
        'loggable_type',
        'loggable_id',
        'submitted_at',
        'confirmed_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function loggable()
    {
        return $this->morphTo();
    }
}
