<?php
namespace App\Models;

class TransferReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'group_id',
        'transfer_count',
        'transfer_amount',
        'transfer_price',
    ];
}
