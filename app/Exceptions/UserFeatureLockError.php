<?php

namespace App\Exceptions;

class UserFeatureLockError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'User has been locked on this feature',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
