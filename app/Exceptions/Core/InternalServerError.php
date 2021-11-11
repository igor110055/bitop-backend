<?php

namespace App\Exceptions\Core;

class InternalServerError extends \App\Exceptions\Exception
{
    public function __construct(
        string $message = 'Internal Server Error',
        $errors = []
    ) {
        parent::__construct($message, 500, $errors);
    }
}
