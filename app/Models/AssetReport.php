<?php
namespace App\Models;

class AssetReport extends UuidModel
{
    protected $fillable = [
        'date',
        'agency_id',
        'currency',
        'unit_price',
        'balance',
        'deposit_amount',
        'manual_deposit_amount',
        'withdraw_amount',
        'manual_withdraw_amount',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
