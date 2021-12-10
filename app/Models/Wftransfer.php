<?php

namespace App\Models;

use App\Exceptions\{
    Core\InternalServerError,
};

class Wftransfer extends UuidModel
{
    const STATUS_INIT = 'init';
    const STATUS_PENDING_PROCESSING = 'pending_processing';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const STATUS = [
        self::STATUS_INIT,
        self::STATUS_PENDING_PROCESSING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];
    public static $status = self::STATUS;
    public static $status_need_update = [
        self::STATUS_INIT,
        self::STATUS_PENDING_PROCESSING,
        self::STATUS_PROCESSING,
    ];

    protected $fillable = [
        'id',
        'order_id',
        'status',
        'bank_account_id',
        'remote_id',
        'total',
        'account_name',
        'merchant_fee',
        'callback_response',
        'response',
        'closed_at',
        'submitted_at',
        'completed_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function bank_account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }

    public function getCallbackUrlAttribute()
    {
        if (config('app.env') === 'local') {
            if (is_null(config('services.wfpay.callback_proxy_domain'))) {
                throw new InternalServerError('Must set WFPAY_CALLBACK_PROXY_DOMAIN in .env file for wfpay callback');
            }
            return config('services.wfpay.callback_proxy_domain')."/api/wfpay/transfer-callback/{$this->id}";
        }
        return config('app.url')."/api/wfpay/transfer-callback/{$this->id}";
    }
}
