<?php
namespace App\Models;

class FeeReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'group_id',
        'order_fee',
        'order_fee_price',
        'withdrawal_fee',
        'withdrawal_fee_price',
        'wallet_fee',
        'wallet_fee_price',
    ];
}
