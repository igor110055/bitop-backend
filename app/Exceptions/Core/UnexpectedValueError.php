<?php

namespace App\Exceptions\Core;

class UnexpectedValueError extends \App\Exceptions\Exception
{
    public function __construct(
        string $message = 'Unexpected Value received',
        $errors = []
    ) {
        parent::__construct($message, 500, $errors);
    }
}
