<?php

namespace App\Exceptions\Auth;

class RealNameVerificationError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'User is still unverified.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
