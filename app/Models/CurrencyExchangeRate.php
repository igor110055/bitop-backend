<?php

namespace App\Models;

class CurrencyExchangeRate extends UuidModel
{
    const PRICE_TYPE_BID = 'bid';
    const PRICE_TYPE_ASK = 'ask';
    const PRICE_TYPE_MID = 'mid';
    const PRICE_TYPES = [
        self::PRICE_TYPE_BID,
        self::PRICE_TYPE_ASK,
        self::PRICE_TYPE_MID,
    ];

    protected $dataFormat = Model::DATE_FORMAT;

    protected $fillable = [
        'group_id',
        'currency',
        'bid',
        'ask',
        'mid',
    ];
}
