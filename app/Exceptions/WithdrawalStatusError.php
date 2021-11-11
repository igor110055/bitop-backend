<?php

namespace App\Exceptions;

class WithdrawalStatusError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Withdrawal expired/confirmed/submitted_confirmed',
        $errors = []
    ) {
        parent::__construct($message, 410, $errors);
    }
}
