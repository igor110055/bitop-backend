<?php

namespace App\Exceptions\Auth;

class WrongMobileLengthError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Incorrect length of the input mobile number',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
