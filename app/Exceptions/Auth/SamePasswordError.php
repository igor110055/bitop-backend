<?php

namespace App\Exceptions\Auth;

class SamePasswordError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Input same password as the old_password.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
