<?php
namespace App\Models;

class Report extends UuidModel
{
    protected $fillable = [
        'date',
        'agency_id',
        'orders',
        'sell_orders',
        'buy_orders',
        'profit',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
