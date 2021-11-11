<?php

namespace App\Exceptions\Account;

class InsufficientBalanceError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Account has insufficient balance for requested action.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
