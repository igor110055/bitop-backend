<?php
namespace App\Models;

class AdReport extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'exchange_rate',
        'group_id',
        'ad_count',
        'buy_ad_count',
        'buy_ad_amount',
        'buy_ad_price',
        'sell_ad_count',
        'sell_ad_amount',
        'sell_ad_price',
    ];
}
