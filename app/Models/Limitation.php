<?php

namespace App\Models;

class Limitation extends UuidModel
{
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPES = [
        self::TYPE_WITHDRAWAL,
    ];

    protected $fillable = [
        'type',
        'coin',
        'min',
        'max',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function limitable()
    {
        return $this->morphTo();
    }
}
