<?php

namespace App\Models;

use App\Exceptions\{
    Core\InternalServerError,
};

class WfpayAccount extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'id',
        'api_url',
        'backstage_url',
        'public_key',
        'private_key',
        'is_active',
        'rank',
        'used_at',
    ];

    protected $hidden = [
        'public_key',
        'private_key',
    ];

    protected $dates = [
        'used_at',
    ];
}
