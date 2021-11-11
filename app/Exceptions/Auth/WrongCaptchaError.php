<?php

namespace App\Exceptions\Auth;

class WrongCaptchaError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Captcha verificatoin failed.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
