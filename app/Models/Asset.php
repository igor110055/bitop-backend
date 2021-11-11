<?php
namespace App\Models;

class Asset extends UuidModel
{
    protected $fillable = [
        'agency_id',
        'currency',
        'balance',
        'unit_price',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function asset_transactions()
    {
        return $this->hasMany(AssetTransaction::class);
    }
}
