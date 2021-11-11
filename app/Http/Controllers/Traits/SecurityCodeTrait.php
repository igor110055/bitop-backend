<?php

namespace App\Http\Controllers\Traits;

use App\Repos\Interfaces\UserRepo;
use App\Models\UserLog;
use App\Exceptions\Auth\WrongSecurityCodeError;
use App\Notifications\{
    SecurityCodeFailUserLockNotification,
};

trait SecurityCodeTrait
{
    protected function checkSecurityCode($user, $code)
    {
        if (!\Hash::check($code, $user->security_code)) {
            $lock = app()->make(UserRepo::class)->authEventRecordLock($user, UserLog::SECURITY_CODE_FAIL);
            if ($lock) {
                $user->notify(new SecurityCodeFailUserLockNotification($lock));
            }
            throw new WrongSecurityCodeError;
        }
        user_log(UserLog::SECURITY_CODE_SUCCESS, ['id' => $user->id], request());
    }
}
