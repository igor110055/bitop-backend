<?php

namespace App\Models;

class UserLog extends UuidModel
{
    const LOG_IN = 'log-in';
    const LOG_IN_LOCK = 'log-in-lock';
    const LOG_IN_UNLOCK = 'log-in-unlock';
    const PASSWORD_FAIL = 'password-fail';
    const PASSWORD_SUCCESS = 'password-success';
    const SECURITY_CODE_LOCK = 'security-code-lock';
    const SECURITY_CODE_UNLOCK = 'security-code-unlock';
    const SECURITY_CODE_FAIL = 'security-code-fail';
    const SECURITY_CODE_SUCCESS = 'security-code-success';
    const ACTIVATE_GOOGLE_AUTH = 'activate-google-auth';
    const DEACTIVATE_GOOGLE_AUTH = 'deactivate-google-auth';
    const ADMIN_LOG_IN = 'admin-log-in';
    const ADMIN_LOG_IN_PASSWORD_FAIL = 'admin-log-in-password-fail';
    const ADMIN_LOG_IN_2FA_FAIL = 'admin-log-in-2fa-fail';
    const ADMIN_LOG_IN_LOCK = 'admin-log-in-lock';
    const ADMIN_LOG_IN_UNLOCK = 'admin-log-in-unlock';

    const ORDER_CREATE = 'order-create';
    const ORDER_CLAIM = 'order-claim';
    const ORDER_REVOKE = 'order-revoke';
    const ORDER_CONFIRM = 'order-confirm';
    const ORDER_CANCEL = 'order-cancel';

    const FAIL_EVENTS = [
        self::PASSWORD_FAIL,
        self::SECURITY_CODE_FAIL,
        self::ADMIN_LOG_IN_PASSWORD_FAIL,
        self::ADMIN_LOG_IN_2FA_FAIL,
    ];

    const SEARCHABLE_EVENTS = [
        self::LOG_IN,
        self::LOG_IN_LOCK,
        self::LOG_IN_UNLOCK,
        self::PASSWORD_FAIL,
        self::PASSWORD_SUCCESS,
        self::SECURITY_CODE_LOCK,
        self::SECURITY_CODE_UNLOCK,
        self::SECURITY_CODE_FAIL,
        self::SECURITY_CODE_SUCCESS,
        self::ADMIN_LOG_IN,
        self::ADMIN_LOG_IN_PASSWORD_FAIL,
        self::ADMIN_LOG_IN_2FA_FAIL,
        self::ADMIN_LOG_IN_LOCK,
        self::ADMIN_LOG_IN_UNLOCK,
    ];

    protected $fillable = [
        'user_id',
        'message',
        'context',
        'remote_addr',
        'user_agent',
    ];

    protected $casts = ['context' => 'array'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
