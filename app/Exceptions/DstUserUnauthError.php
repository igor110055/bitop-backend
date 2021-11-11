<?php

namespace App\Exceptions;

class DstUserUnauthError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Targer user is unauthenticated.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
