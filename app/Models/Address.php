<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends UuidModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'label',
        'coin',
        'network',
        'address',
        'tag',
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
