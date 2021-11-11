<?php

namespace App\Exceptions\Core;

class WrongRequestHeaderError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Wrong or missing request header field',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
