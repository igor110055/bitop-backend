<?php
namespace App\Models;

class WithdrawalDepositReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'group_id',
        'withdrawal_count',
        'withdrawal_amount',
        'withdrawal_pricd',
        'deposit_count',
        'deposit_amount',
        'deposit_price',
    ];
}
