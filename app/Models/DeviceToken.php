<?php

namespace App\Models;

class DeviceToken extends UuidModel
{
    const PLATFORM_WEB = 'web';
    const PLATFORM_IOS = 'ios';
    const PLATFORM_ANDROID = 'android';

    const PLATFORMS = [
        self::PLATFORM_WEB,
        self::PLATFORM_IOS,
        self::PLATFORM_ANDROID,
    ];

    const SERVICE_FCM = 'fcm';
    const SERVICE_JPUSH = 'jpush';

    const SERVICES = [
        self::SERVICE_FCM,
        self::SERVICE_JPUSH,
    ];

    protected $fillable = [
        'user_id',
        'platform',
        'service',
        'token',
        'is_active',
        'last_active_at',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected $dates = ['last_active_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
