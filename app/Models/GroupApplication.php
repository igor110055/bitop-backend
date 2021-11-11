<?php

namespace App\Models;

class GroupApplication extends UuidModel
{
    const STATUS_PROCESSING = 'processing';
    const STATUS_PASS = 'pass';
    const STATUS_REJECT = 'reject';

    const STATUSES = [
        self::STATUS_PROCESSING,
        self::STATUS_PASS,
    ];

    protected $fillable = [
        'user_id',
        'group_name',
        'description',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }
}
