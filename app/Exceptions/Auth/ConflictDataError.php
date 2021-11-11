<?php

namespace App\Exceptions\Auth;

class ConflictDataError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Data provided conflicts with existing one',
        $errors = []
    ) {
        parent::__construct($message, 409, $errors);
    }
}
