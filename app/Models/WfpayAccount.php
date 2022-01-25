<?php

namespace App\Models;

use App\Exceptions\{
    Core\InternalServerError,
};

class WfpayAccount extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
        'configs' => 'array',
    ];

    protected $fillable = [
        'id',
        'name',
        'api_url',
        'backstage_url',
        'public_key',
        'private_key',
        'is_active',
        'rank',
        'transfer_rank',
        'configs',
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
