<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Log;
use App\Exceptions\{
    Core\BadRequestError,
    Auth\WrongTFACodeError,
    VendorException,
};
use App\Repos\Interfaces\{
    UserRepo,
    TwoFactorAuthRepo,
    AdminActionRepo,
};
use App\Models\{
    User,
    TwoFactorAuth,
    UserLog,
    AdminAction,
};
use DB;

class TwoFactorAuthService implements TwoFactorAuthServiceInterface
{
    public function __construct(
        UserRepo $UserRepo,
        TwoFactorAuthRepo $TFArepo,
        AdminActionRepo $AdminActionRepo
    ) {
        $this->UserRepo = $UserRepo;
        $this->TwoFactorAuthRepo = $TFArepo;
        $this->AdminActionRepo = $AdminActionRepo;
    }

    public function preActivate(User $user)
    {
        try {
            $google2fa = new Google2FA();
            $secret =  $google2fa->generateSecretKey();
            $url = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            );
        } catch (\Throwable $e) {
            throw new VendorException;
        }
        if ($tfa = $this->TwoFactorAuthRepo->getUserTwoFactorAuth($user, TwoFactorAuth::GOOGLE_AUTH)) {
            if ($tfa->is_active) {
                throw new BadRequestError;
            }
            $this->TwoFactorAuthRepo->setAttribute($tfa, ['secret' => $secret]);
        } else {
            $this->TwoFactorAuthRepo->create($user, $secret);
        }
        return ['url' => $url, 'secret' => $secret];
    }

    public function activate(User $user, string $code)
    {
        if (!($tfa = $this->TwoFactorAuthRepo->getUserTwoFactorAuth($user, TwoFactorAuth::GOOGLE_AUTH))) {
            throw new BadRequestError;
        }

        if ($this->verifyKey($tfa->secret, $code)) {
            DB::transaction(function () use ($user, $tfa) {
                $this->TwoFactorAuthRepo->setAttribute($tfa, ['is_active' => true]);
                $this->UserRepo->setAttribute($user, ['two_factor_auth' => TwoFactorAuth::GOOGLE_AUTH]);
                user_log(UserLog::ACTIVATE_GOOGLE_AUTH, ['id' => $user->id], request());
            });
        } else {
            throw new WrongTFACodeError;
        }
    }

    public function deactivate(User $user, string $code)
    {
        if (!($tfa = $this->TwoFactorAuthRepo->getUserTwoFactorAuth($user, TwoFactorAuth::GOOGLE_AUTH))) {
            throw new BadRequestError;
        }

        if ($this->verifyKey($tfa->secret, $code)) {
            DB::transaction(function () use ($user, $tfa) {
                $this->TwoFactorAuthRepo->setAttribute($tfa, ['is_active' => false]);
                $this->UserRepo->setAttribute($user, ['two_factor_auth' => null]);
                user_log(UserLog::DEACTIVATE_GOOGLE_AUTH, ['id' => $user->id], request());
            });
        } else {
            throw new WrongTFACodeError;
        }
    }

    public function deactivateWithoutVerify(User $user, string $description = null)
    {
        if (!($tfa = $this->TwoFactorAuthRepo->getUserTwoFactorAuth($user, TwoFactorAuth::GOOGLE_AUTH))) {
            throw new BadRequestError;
        }

        DB::transaction(function () use ($user, $tfa, $description) {
            $this->TwoFactorAuthRepo->setAttribute($tfa, ['is_active' => false]);
            $this->UserRepo->setAttribute($user, ['two_factor_auth' => null]);
            $this->AdminActionRepo->createByApplicable($tfa, [
                'admin_id' => \Auth::id(),
                'type' => AdminAction::TYPE_DEACTIVATE_TFA,
                'description' => $description,
            ]);
        });
    }

    public function verify(User $user, string $code)
    {
        if (!($tfa = $this->TwoFactorAuthRepo->getUserTwoFactorAuth($user, TwoFactorAuth::GOOGLE_AUTH))) {
            throw new BadRequestError;
        }
        if (!$tfa->is_active) {
            throw new BadRequestError;
        }
        return $this->verifyKey($tfa->secret, $code);
    }

    protected function verifyKey(string $secret, string $code)
    {
        try {
            $google2fa = new Google2FA();
            return $google2fa->verifyKey($secret, $code);
        } catch (\Throwable $e) {
            throw new VendorException;
        }
    }
}
