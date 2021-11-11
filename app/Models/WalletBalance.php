<?php

namespace App\Models;

class WalletBalance extends UuidModel
{
    protected $fillable = [
        'coin',
        'balance',
        'address',
        'tag'
    ];
}
