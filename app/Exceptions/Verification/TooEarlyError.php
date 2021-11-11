<?php

namespace App\Exceptions\Verification;

class TooEarlyError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Verification is not ready for re-sending',
        $errors = []
    ) {
        parent::__construct($message, 425, $errors);
    }
}
