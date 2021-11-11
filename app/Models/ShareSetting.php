<?php
namespace App\Models;

class ShareSetting extends UuidModel
{
    protected $fillable = [
        'group_id',
        'user_id',
        'percentage',
        'is_prior',
        'is_active',
    ];
    protected $casts = [
        'is_prior' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
