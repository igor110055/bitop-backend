<?php

namespace App\Models;

class Agency extends Model
{
    protected $table = 'agencies';
    const DEFAULT_AGENCY_ID = 'default';

    protected $fillable = [
        'id',
        'name',
    ];

    public function agents()
    {
        return $this->hasMany(User::class, 'agency_id');
    }
}
