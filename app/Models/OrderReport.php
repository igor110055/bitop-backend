<?php
namespace App\Models;

class OrderReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'group_id',
        'order_count',
        'order_amount',
        'share_amount',
        'order_price',
        'share_price',
        'profit',
    ];
}
