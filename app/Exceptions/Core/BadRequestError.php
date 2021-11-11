<?php

namespace App\Exceptions\Core;

class BadRequestError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Bad Request',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
