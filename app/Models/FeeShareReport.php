<?php

namespace App\Models;

class FeeShareReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'group_id',
        'share_amount',
        'share_price',
    ];
}
