<?php

namespace App\Exceptions;

class WalletBalanceInsufficientError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Wallet balance insufficient',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
