<?php
namespace App\Models;

use Dec\Dec;

class Account extends UuidModel
{
    protected $fillable = [
        'user_id',
        'coin',
        'balance',
        'locked_balance',
        'unit_price',
        'address',
        'tag',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getAvailableBalanceAttribute()
    {
        return (string)Dec::sub($this->balance, $this->locked_balance);
    }
}
