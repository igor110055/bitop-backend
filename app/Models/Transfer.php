<?php

namespace App\Models;

use Carbon\Carbon;

class Transfer extends UuidModel
{
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'src_user_id',
        'dst_user_id',
        'src_account_id',
        'dst_account_id',
        'coin',
        'amount',
        'message',
        'memo',
        'confirmed_at',
        'canceled_at',
        'expired_at',
    ];

    protected $dates = [
        'confirmed_at',
        'canceled_at',
        'expired_at',
    ];

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    public function verifications()
    {
        return $this->morphMany(Verification::class, 'verificable');
    }

    public function src_user()
    {
        return $this->belongsTo(User::class, 'src_user_id');
    }

    public function dst_user()
    {
        return $this->belongsTo(User::class, 'dst_user_id');
    }

    public function system_actions()
    {
        return $this->morphMany(SystemAction::class, 'applicable');
    }

    public function getIsConfirmedAttribute()
    {
        return $this->confirmed_at !== null;
    }

    public function getIsCanceledAttribute()
    {
        return $this->canceled_at !== null;
    }

    public function getIsExpiredAttribute()
    {
        if (is_null($this->expired_at)) {
            return false;
        }
        return $this->expired_at->lt(Carbon::now());
    }

    public function getStatusAttribute()
    {
        if ($this->canceled_at) {
            return self::STATUS_CANCELED;
        } elseif ($this->confirmed_at) {
            return self::STATUS_COMPLETED;
        } else {
            return self::STATUS_PROCESSING;
        }
    }
}
