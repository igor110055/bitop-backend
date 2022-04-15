<?php
namespace App\Models;

class ExchangeRate extends UuidModel
{
    const TYPE_SYSTEM = 'system';
    const TYPE_FIXED = 'fixed';
    const TYPE_FLOATING = 'floating';
    const TYPE_DIFF = 'diff';

    const TYPES = [
        self::TYPE_SYSTEM,
        self::TYPE_FIXED,
        self::TYPE_FLOATING,
        self::TYPE_DIFF,
    ];

    protected $fillable = [
        'merchant_id',
        'coin',
        'type',
        'bid',
        'ask',
        'bid_diff',
        'ask_diff',
        'diff',
    ];

    public function merchant()
    {
        return $this->belongTo(Merchant::class);
    }
}
