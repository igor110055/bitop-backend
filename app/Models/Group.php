<?php

namespace App\Models;

class Group extends Model
{
    const DEFAULT_GROUP_ID = 'default';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'description',
        'is_joinable',
    ];

    protected $hidden = [
        'user_id',
    ];

    protected $casts = ['is_joinable' => 'boolean'];

    public function getUserCountAttribute()
    {
        return $this->users->count();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function fee_settings()
    {
        return $this->morphMany(FeeSetting::class, 'applicable');
    }

    public function share_settings()
    {
        return $this->hasMany(ShareSetting::class);
    }

    public function group_invitations()
    {
        return $this->hasMany(GroupInvitation::class);
    }

    public function limitations()
    {
        return $this->morphMany(Limitation::class, 'limitable');
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }
}
