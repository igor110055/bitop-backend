<?php

namespace App\Repos\Interfaces;

use App\Models\{
    User,
    TwoFactorAuth,
};

interface TwoFactorAuthRepo
{
    public function find($id);
    public function findOrFail($id);
    public function setAttribute(TwoFactorAuth $tfa, array $array);
    public function create(User $user, string $secret, string $method = TwoFactorAuth::GOOGLE_AUTH);
    public function getUserTwoFactorAuth(User $user, string $method = TwoFactorAuth::GOOGLE_AUTH);
}
