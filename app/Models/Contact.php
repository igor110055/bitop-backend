<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends UuidModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'contact_id',
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
