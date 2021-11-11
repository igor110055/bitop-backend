<?php

namespace App\Exceptions\Account;

class InsufficientLockedBalanceException extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Amount tried to unlock exceeds locked balance.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
