<?php

namespace App\Models;

class UserLock extends UuidModel
{
    const LOGIN = 'login';
    const SECURITY_CODE = 'security-code';
    const ADMIN = 'admin';
    const BACKEND_LOGIN_PASSWORD = 'backend-login-password';
    const BACKEND_LOGIN_2FA = 'backend-login-2fa';
    const TRANSFER = 'transfer';
    const WITHDRAWAL = 'withdrawal';

    const RECORD_IP = [
        self::LOGIN,
        self::SECURITY_CODE,
        self::BACKEND_LOGIN_PASSWORD,
    ];

    const FRONTEND_TYPES = [
        self::ADMIN,
        self::SECURITY_CODE,
        self::LOGIN,
    ];

    const BACKEND_TYPES = [
        self::ADMIN,
        self::BACKEND_LOGIN_PASSWORD,
        self::BACKEND_LOGIN_2FA,
    ];

    const FEATURE_TYPES = [
        self::TRANSFER,
        self::WITHDRAWAL,
    ];

    protected $fillable = [
        'user_id',
        'ip',
        'type',
        'is_active',
        'expired_at',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected $dates = ['expired_at'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }

    public function system_actions()
    {
        return $this->morphMany(SystemAction::class, 'applicable');
    }
}
