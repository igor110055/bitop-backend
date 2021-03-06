<?php

namespace App\Models;

use App\Exceptions\{
    Core\InternalServerError,
};

class Wfpayment extends UuidModel
{
    const METHOD_BANK = 'bank';
    const METHOD_WECHAT = 'wechat';
    const METHOD_ALIPAY = 'alipay';
    const METHODS = [
        self::METHOD_BANK,
        self::METHOD_WECHAT,
        self::METHOD_ALIPAY,
    ];
    public static $methods = self::METHODS;
    public static $limits = [
        self::METHOD_BANK => [
            'min' => 100,
            'max' => 50000,
        ],
        self::METHOD_WECHAT => [
            'min' => 3000,
            'max' => 20000,
        ],
        self::METHOD_ALIPAY => [
            'min' => 3000,
            'max' => 20000,
        ],
    ];

    const STATUS_INIT = 'init';
    const STATUS_PENDINT_ALLOCATION = 'pending_allocation';
    const STATUS_PENDINT_PAYMENT = 'pending_payment';
    const STATUS_PENDINT_CONFIRMATION = 'pending_confirmation';
    const STATUS_PENDINT_COMPLETED = 'pending_completed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_DENIED = 'denied';
    const STATUS_PAYMENT_EXPIRED = 'payment_expired';
    const STATUS = [
        self::STATUS_INIT,
        self::STATUS_PENDINT_ALLOCATION,
        self::STATUS_PENDINT_PAYMENT,
        self::STATUS_PENDINT_CONFIRMATION,
        self::STATUS_PENDINT_COMPLETED,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_DENIED,
        self::STATUS_PAYMENT_EXPIRED,
    ];
    public static $status = self::STATUS;
    public static $status_need_update = [
        self::STATUS_INIT,
        self::STATUS_PENDINT_ALLOCATION,
        self::STATUS_PENDINT_PAYMENT,
        self::STATUS_PENDINT_CONFIRMATION,
        self::STATUS_PENDINT_COMPLETED,
    ];

    protected $casts = [
        'payment_info' => 'array',
        'response' => 'array',
    ];

    protected $fillable = [
        'id',
        'order_id',
        'status',
        'remote_id',
        'total',
        'guest_payment_amount',
        'wfpay_account_id',
        'real_name',
        'payment_method',
        'payment_url',
        'payment_info',
        'merchant_fee',
        'callback_response',
        'response',
        'closed_at',
        'completed_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function wfpay_account()
    {
        return $this->belongsTo(WfpayAccount::class, 'wfpay_account_id');
    }

    public function getCallbackUrlAttribute()
    {
        if (config('app.env') === 'local') {
            if (is_null(config('services.ngrok.domain'))) {
                throw new InternalServerError('Must set NGROK_DOMAIN in .env file for wfpay callback');
            }
            return config('services.ngrok.domain')."/api/wfpay/payment-callback/{$this->id}";
        }
        return config('app.url')."/api/wfpay/payment-callback/{$this->id}";
    }

    public function getReturnUrlAttribute()
    {
        return config('app.url')."/orders/".$this->order->id;
    }
}
