<?php

namespace App\Exceptions;

class WithdrawLimitationError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Withdraw amount under minimum or exceed maximum.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
