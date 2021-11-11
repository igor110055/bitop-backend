<?php

namespace App\Models;

use Carbon\Carbon;
use PragmaRX\Google2FA\Google2FA;

use DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class Verification extends UuidModel
{
    use Notifiable;

    const TYPE_EMAIL = 'email';
    const TYPE_MOBILE = 'mobile';
    const TYPE_PASSWORD = 'password';
    const TYPE_SECURITY_CODE = 'security-code';
    const TYPE_TRANSFER_CONFIRMATION = 'transfer-confirmation';
    const TYPE_ORDER_CONFIRMATION = 'order-confirmation';
    const TYPE_WITHDRAWAL_CONFIRMATION = 'withdrawal-confirmation';
    const TYPE_RESET_EMAIL = 'reset-email';
    const TYPE_RESET_MOBILE = 'reset-mobile';
    const TYPE_DEACTIVATE_TFA = 'deactivate-tfa';
    const TYPES = [
        self::TYPE_EMAIL,
        self::TYPE_MOBILE,
        self::TYPE_PASSWORD,
        self::TYPE_SECURITY_CODE,
        self::TYPE_TRANSFER_CONFIRMATION,
        self::TYPE_ORDER_CONFIRMATION,
        self::TYPE_WITHDRAWAL_CONFIRMATION,
        self::TYPE_RESET_EMAIL,
        self::TYPE_RESET_MOBILE,
        self::TYPE_DEACTIVATE_TFA,
    ];
    const CODE_LENGTH = 7;
    const CODE_TYPE_DIGIT = 'digit';
    const CODE_TYPE_LOWER = 'lower';
    const CODE_TYPE_UPPER = 'upper';
    const CODE_TYPE_ALPHA = 'alpha';
    const CODE_TYPE_DIGIT_LOWER = 'digit_lower';
    const CODE_TYPE_DIGIT_UPPER = 'digit_upper';
    const CODE_TYPE_DIGIT_ALPHA = 'digit_alpha';

    protected $fillable = [
        'verificable_id',
        'verificable_type',
        'type',
        'channel',
        'data',
        'code',
        'tries',
        'notified_at',
        'expired_at',
    ];
    protected $casts = [
        'channel' => 'array',
    ];
    protected $dates = ['notified_at', 'expired_at', 'verified_at'];
    protected $visible = ['id', 'type', 'data', 'expired_at'];

    public function verificable()
    {
        return $this->morphTo();
    }

    public function getIsAvailableAttribute()
    {
        return !($this->is_verified
            or $this->is_expired
            or ($this->tries >= config('core.verification.max_tries'))
        );
    }

    public function getIsExpiredAttribute()
    {
        if (is_null($this->expired_at)) {
            return false;
        }
        $now = new Carbon;
        return $this->expired_at->lt($now);
    }

    public function getIsVerifiedAttribute()
    {
        return $this->verified_at !== null;
    }

    public static function codeLength($type)
    {
        assert(in_array($type, Verification::TYPES));
        return config("core.verification.code.length.$type");
    }

    public static function codeType($type)
    {
        assert(in_array($type, Verification::TYPES));
        return config("core.verification.code.type.$type");
    }

    public static function timeout($type)
    {
        assert(in_array($type, Verification::TYPES));
        return config("core.verification.timeout.$type");
    }

    public static function channel($type)
    {
        assert(in_array($type, Verification::TYPES));
        return config("core.verification.channel.$type");
    }

    # for Notifiable
    public function routeNotificationForNexmo()
    {
        return $this->data;
    }

    public function routeNotificationForMail()
    {
        return $this->data;
    }

    public function routeNotificationForSms(?Notification $notication = null)
    {
        return $this->data;
    }
}
