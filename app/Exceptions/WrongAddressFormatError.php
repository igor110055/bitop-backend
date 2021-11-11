<?php

namespace App\Exceptions;

class WrongAddressFormatError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Wrong address format',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
