<?php

namespace App\Exceptions\Verification;

class WrongCodeError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Wrong verification code',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
