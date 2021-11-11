<?php

namespace App\Repos\DB;

use App\Exceptions\{
    UnavailableStatusError,
};
use App\Models\{
    User,
    TwoFactorAuth,
};

class TwoFactorAuthRepo implements \App\Repos\Interfaces\TwoFactorAuthRepo
{
    protected $TFA;

    public function __construct(TwoFactorAuth $TFA)
    {
        $this->TFA = $TFA;
    }

    public function find($id)
    {
        return $this->TFA->find($id);
    }

    public function findOrFail($id)
    {
        return $this->TFA->findOrFail($id);
    }

    public function setAttribute(TwoFactorAuth $tfa, array $array)
    {
        if ($this->TFA
            ->where('id', $tfa->id)
            ->update($array) !== 1) {
            throw new UnavailableStatusError;
        }
    }

    public function create(User $user, string $secret, string $method = TwoFactorAuth::GOOGLE_AUTH)
    {
        return $user->two_factor_auths()->create([
            'method' => $method,
            'secret' => $secret,
        ]);
    }

    public function getUserTwoFactorAuth(User $user, string $method = TwoFactorAuth::GOOGLE_AUTH)
    {
        return $user->two_factor_auths()
            ->where('method', $method)
            ->first();
    }
}
