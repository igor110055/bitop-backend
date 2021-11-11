<?php

namespace App\Exceptions\Auth;

class WrongSecurityCodeError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Input wrong security code.',
        $errors = []
    ) {
        parent::__construct($message, 401, $errors);
    }
}
