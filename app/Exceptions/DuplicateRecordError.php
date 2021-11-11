<?php

namespace App\Exceptions;

class DuplicateRecordError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Records already exists.',
        $errors = []
    ) {
        parent::__construct($message, 409, $errors);
    }
}
