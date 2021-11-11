<?php

namespace App\Models;

class FeeCost extends UuidModel
{
    protected $fillable = [
        'date',
        'coin',
        'params',
        'cost',
    ];

    protected $casts = [
        'params' => 'array',
    ];
}
