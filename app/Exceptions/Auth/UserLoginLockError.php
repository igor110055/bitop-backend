<?php

namespace App\Exceptions\Auth;

class UserLoginLockError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'User has been locked for amount of time.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
