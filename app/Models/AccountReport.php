<?php
namespace App\Models;

class AccountReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'group_id',
        'balance',
        'balance_price',
    ];
}
