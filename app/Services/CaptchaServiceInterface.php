<?php

namespace App\Services;

interface CaptchaServiceInterface
{
    public function verify(string $token);
}
