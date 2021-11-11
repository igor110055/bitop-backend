<?php

namespace App\Models;

class TwoFactorAuth extends UuidModel
{
    const GOOGLE_AUTH = 'google-auth';

    protected $fillable = [
        'user_id',
        'method',
        'secret',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }
}
