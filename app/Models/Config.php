<?php
namespace App\Models;

class Config extends UuidModel
{
    const ATTRIBUTE_WALLET = 'wallet';
    const ATTRIBUTE_WITHDRAWAL_FEE_FACTOR = 'withdrawal-fee-factor';
    const ATTRIBUTE_WITHDRAWAL_LIMIT = 'withdrawal-limit';
    const ATTRIBUTE_APP_VERSION = 'app-version';
    const ATTRIBUTE_INVITATION_REQUIRED = 'invitation-required';
    const ATTRIBUTE_PAYMENT_WINDOW = 'payment-window';
    const ATTRIBUTE_EXPRESS_PAYMENT_WINDOW = 'express-payment-window';
    const ATTRIBUTE_EXPRESS_AUTO_RELEASE_LIMIT = 'express-auto-release-limit';
    const ATTRIBUTES = [
        self::ATTRIBUTE_WALLET,
        self::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR,
        self::ATTRIBUTE_WITHDRAWAL_LIMIT,
        self::ATTRIBUTE_APP_VERSION,
        self::ATTRIBUTE_INVITATION_REQUIRED,
        self::ATTRIBUTE_PAYMENT_WINDOW,
        self::ATTRIBUTE_EXPRESS_PAYMENT_WINDOW,
        self::ATTRIBUTE_EXPRESS_AUTO_RELEASE_LIMIT,
    ];
    const DEFAULT = [
        self::ATTRIBUTE_INVITATION_REQUIRED => true,
        self::ATTRIBUTE_PAYMENT_WINDOW => [
            'min' => 360,
            'max' => 1440,
        ],
        self::ATTRIBUTE_EXPRESS_PAYMENT_WINDOW => 25,
        self::ATTRIBUTE_EXPRESS_AUTO_RELEASE_LIMIT => [
            'min' => 0,
            'max' => 30000,
        ],
    ];

    protected $fillable = [
        'admin_id',
        'attribute',
        'value',
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
