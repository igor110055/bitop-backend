<?php

namespace App\Models;

use Carbon\Carbon;

use App\Exceptions\{
    Core\InternalServerError,
};

class Withdrawal extends UuidModel
{
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELED = 'canceled';

    const EXPIRED = 'expired';
    const BAD_REQUEST = 'bad-request';

    const CANCEL_REASON = [
        self::EXPIRED,
        self::BAD_REQUEST,
    ];

    protected $fillable = [
        'user_id',
        'account_id',
        'wallet_id',
        'transaction',
        'coin',
        'address',
        'tag',
        'amount',
        'src_amount',
        'dst_amount',
        'fee',
        'wallet_fee',
        'wallet_fee_coin',
        'is_full_payment',
        'callback',
        'fee_setting_id',
        'message',
        'confirmed_at',
        'submitted_at',
        'submitted_confirmed_at',
        'notified_at',
        'expired_at',
        'canceled_at',
        'response',
        'callback_response',
    ];

    protected $hidden = [
        'src_amount',
        'dst_amount',
        'wallet_fee',
        'wallet_fee_coin',
        'is_full_payment',
        'callback',
        'response',
        'callback_response',
        'submitted_at',
        'submitted_confirmed_at',
    ];

    protected $casts = [
        'is_full_payment' => 'boolean',
        'response' => 'array',
        'callback_response' => 'array',
    ];

    protected $dates = [
        'confirmed_at',
        'submitted_at',
        'submitted_confirmed_at',
        'notified_at',
        'expired_at',
        'canceled_at',
    ];

    protected $appends = ['status'];

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

    public function fee_setting()
    {
        return $this->belongsTo(FeeSetting::class, 'fee_setting_id');
    }

    public function verifications()
    {
        return $this->morphMany(verification::class, 'verificable');
    }

    public function system_actions()
    {
        return $this->morphMany(SystemAction::class, 'applicable');
    }

    public function export_logs()
    {
        return $this->morphMany(ExportLog::class, 'loggable');
    }

    public function getIsConfirmedAttribute()
    {
        return $this->confirmed_at !== null;
    }

    public function getIsSubmittedConfirmedAttribute()
    {
        return $this->submitted_confirmed_at !== null;
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

    public function getIsNotifiedAttribute()
    {
        return $this->notified_at !== null;
    }

    public function getStatusAttribute()
    {
        if ($this->canceled_at) return self::STATUS_CANCELED;
        return is_null($this->notified_at)? self::STATUS_PROCESSING : self::STATUS_COMPLETED;
    }

    public function getCallback()
    {
        if (config('app.env') === 'local') {
            if (is_null(config('services.wallet.callback_proxy_domain'))) {
                throw new InternalServerError('Must set WALLET_CALLBACK_PROXY_DOMAIN in .env file for wallet withdrawal callback');
            }
            return config('services.wallet.callback_proxy_domain')."/api/wallet/withdrawal-callback/{$this->id}";
        }
        return config('app.url')."/api/wallet/withdrawal-callback/{$this->id}";
    }
}
