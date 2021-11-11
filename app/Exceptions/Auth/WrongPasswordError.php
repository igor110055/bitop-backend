<?php

namespace App\Exceptions\Auth;

class WrongPasswordError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Input wrong password.',
        $errors = []
    ) {
        parent::__construct($message, 401, $errors);
    }
}
