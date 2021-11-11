<?php
namespace App\Models;

class Manipulation extends UuidModel
{
    protected $fillable = [
        'user_id',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    public function wallet_balance_logs()
    {
        return $this->morphMany(WalletBalanceLog::class, 'wlogable');
    }

    public function asset_transactions()
    {
        return $this->morphMany(AssetTransaction::class, 'transactable');
    }
}
