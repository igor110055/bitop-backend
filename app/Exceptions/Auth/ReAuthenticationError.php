<?php

namespace App\Exceptions\Auth;

class ReAuthenticationError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'User has been authenticated.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
