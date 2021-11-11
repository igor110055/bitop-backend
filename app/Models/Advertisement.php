<?php

namespace App\Models;

use Dec\Dec;

class Advertisement extends RandomIDModel
{
    const ID_SIZE = 14;
    const TYPE_BUY = 'buy';
    const TYPE_SELL = 'sell';
    const MAX_SPREAD_PERCENTAGE = 10;

    const STATUS_AVAILABLE = 'available';
    const STATUS_COMPLETED = 'completed';
    const STATUS_UNAVAILABLE = 'unavailable';
    const STATUS_DELETED = 'deleted';

    const STATUS = [
        self::STATUS_AVAILABLE,
        self::STATUS_UNAVAILABLE,
        self::STATUS_COMPLETED,
        self::STATUS_DELETED,
    ];

    const TYPES = [
        self::TYPE_BUY,
        self::TYPE_SELL,
    ];

    public static $types = [
        self::TYPE_BUY,
        self::TYPE_SELL,
    ];

    protected $dates = [
        'deleted_at',
    ];

    protected $casts = [
        'nationality' => 'array',
    ];

    protected $fillable = [
        'id',
        'reference_id',
        'user_id',
        'type',
        'status',
        'coin',
        'amount',
        'remaining_amount',
        'fee',
        'remaining_fee',
        'currency',
        'unit_price',
        'terms',
        'message',
        'min_trades',
        'min_limit',
        'max_limit',
        'payment_window',
        'nationality',
        'fee_setting_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reference()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function references()
    {
        return $this->hasMany(Advertisement::class, 'reference_id');
    }

    public function bank_accounts()
    {
        return $this->morphToMany(BankAccount::class, 'bank_account_payable')->withTimestamps();
    }

    public function fee_setting()
    {
        return $this->belongsTo(FeeSetting::class, 'fee_setting_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }

    public function getSelfAttribute()
    {
        $user = auth()->user();
        return data_get($user, 'id') === $this->user_id;
    }

    public function getRemainingBelowLimitAttribute()
    {
        $remain_value = Dec::mul($this->remaining_amount, $this->unit_price);
        return Dec::lt($remain_value, $this->min_limit);
    }
}
