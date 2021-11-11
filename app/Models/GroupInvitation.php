<?php

namespace App\Models;

class GroupInvitation extends UuidModel
{
    const CODE_LENGTH = 10;
    const CODE_TYPE_DIGIT_ALL = 'digit_all';

    protected $fillable = [
        'group_id',
        'invitation_code',
        'used_at',
        'expired_at',
    ];

    protected $dates = ['used_at', 'expired_at'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
