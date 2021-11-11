<?php

namespace App\Exceptions\Auth;

class WrongTFACodeError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Input wrong two factor auth code.',
        $errors = []
    ) {
        parent::__construct($message, 401, $errors);
    }
}
