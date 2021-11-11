<?php
namespace App\Models;

class AssetTransaction extends UuidModel
{
    const TYPE_SELL_ORDER = 'sell-order';
    const TYPE_BUY_ORDER = 'buy-order';
    const TYPE_MANUAL_DEPOSIT = 'manual-deposit';
    const TYPE_MANUAL_WITHDRAWAL = 'manual-withdrawal';
    const TYPES = [
        self::TYPE_SELL_ORDER,
        self::TYPE_BUY_ORDER,
        self::TYPE_MANUAL_DEPOSIT,
        self::TYPE_MANUAL_WITHDRAWAL,
    ];

    protected $fillable = [
        'asset_id',
        'type',
        'amount',
        'balance',
        'unit_price',
        'result_unit_price',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function transactable()
    {
        return $this->morphTo();
    }
}
