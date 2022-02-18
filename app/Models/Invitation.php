<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitation extends UuidModel
{
    use SoftDeletes;

    const CODE_LENGTH = 10;
    const CODE_TYPE_DIGIT_ALL = 'digit_all';

    protected $fillable = [
        'id',
        'user_id',
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
