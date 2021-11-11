<?php

namespace App\Models;

class CoinExchangeRate extends UuidModel
{
    protected $dataFormat = Model::DATE_FORMAT;

    protected $fillable = [
        'coin',
        'price',
    ];
}
