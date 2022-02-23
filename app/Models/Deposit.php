<?php

namespace App\Models;

class Deposit extends UuidModel
{
    protected $fillable = [
        'user_id',
        'account_id',
        'wallet_id',
        'type',
        'transaction',
        'coin',
        'address',
        'tag',
        'amount',
        'confirmed_at',
        'callback_response',
    ];

    protected $hidden = [
        'callback_response',
    ];

    protected $dates = ['confirmed_at'];

    protected $casts = [
        'callback_response' => 'array',
    ];

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    public function wallet_balance_logs()
    {
        return $this->morphMany(WalletBalanceLog::class, 'wlogable');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function export_logs()
    {
        return $this->morphMany(ExportLog::class, 'loggable');
    }
}
