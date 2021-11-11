<?php

namespace App\Exceptions\Verification;

class ExpiredVerificationError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Verification is already expired.',
        $errors = []
    ) {
        parent::__construct($message, 410, $errors);
    }
}
