<?php

namespace App\Exceptions\Core;

class UnknownError extends \App\Exceptions\Exception
{
    public function __construct(
        string $message = 'Unknown Error',
        $errors = []
    ) {
        parent::__construct($message, 500, $errors);
    }
}
