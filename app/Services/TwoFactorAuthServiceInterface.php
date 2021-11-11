<?php

namespace App\Services;

use App\Models\User;

interface TwoFactorAuthServiceInterface
{
    public function preActivate(User $user);
    public function activate(User $user, string $code);
    public function deactivate(User $user, string $code);
    public function deactivateWithoutVerify(User $user, string $description = null);
    public function verify(User $user, string $code);
}
