<?php

namespace App\Models;

class BankAccount extends UuidModel
{
    const REASON_NAME_NOT_MATCHED = 'name_not_matched';
    const REASON_PHONETIC_NAME_NOT_MATCHED = 'phontic_name_not_matched';
    const REASON_INVALID_NAME = 'invalid_name';
    const REASON_INVALID_PHONETIC_NAME = 'invalid_phonetic_name';
    const REASON_INVALID_BRANCH_NAME = 'invalid_branch_name';
    const REASON_INVALID_BRANCH_PHONETIC_NAME = 'invalid_branch_phonetic_name';
    const REASON_INVALID_ACCOUNT = 'invalid_account';
    
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_DELETED = 'deleted';
    
    const REASONS = [
        self::REASON_NAME_NOT_MATCHED,
        self::REASON_PHONETIC_NAME_NOT_MATCHED,
        self::REASON_INVALID_NAME,
        self::REASON_INVALID_PHONETIC_NAME,
        self::REASON_INVALID_BRANCH_NAME,
        self::REASON_INVALID_BRANCH_PHONETIC_NAME,
        self::REASON_INVALID_ACCOUNT,
    ];

    protected $dataFormat = Model::DATE_FORMAT;

    protected $fillable = [
        'user_id',
        'bank_id',
        'currency',
        'account',
        'type',
        'name',
        'phonetic_name',
        'bank_branch_name',
        'bank_branch_phonetic_name',
        'verified_at',
        'deleted_at',
    ];

    protected $casts = [
        'currency' => 'array',
    ];
    protected $dates = ['verified_at'];

    public function getIsVerifiedAttribute()
    {
        return $this->verified_at !== null;
    }

    public function getStatusAttribute()
    {
        if (!is_null($this->deleted_at)) {
            return static::STATUS_DELETED;
        } elseif (is_null($this->verified_at)) {
            return static::STATUS_PENDING;
        } else {
            return static::STATUS_ACTIVE;
        }
    }

    public function getBankNameAttribute()
    {
        return data_get($this->bank, 'name');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function advertisements()
    {
        return $this->morphedByMany(Advertisement::class, 'bank_account_payable')->withTimestamps();
    }

    public function orders()
    {
        return $this->morphedByMany(Order::class, 'bank_account_payable')->withTimestamps();
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }
}
