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
    const ATTRIBUTES = [
        self::ATTRIBUTE_WALLET,
        self::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR,
        self::ATTRIBUTE_WITHDRAWAL_LIMIT,
        self::ATTRIBUTE_APP_VERSION,
        self::ATTRIBUTE_INVITATION_REQUIRED,
        self::ATTRIBUTE_PAYMENT_WINDOW,
    ];
    const DEFAULT = [
        self::ATTRIBUTE_INVITATION_REQUIRED => true,
        self::ATTRIBUTE_PAYMENT_WINDOW => [
            'min' => 360,
            'max' => 1440,
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
