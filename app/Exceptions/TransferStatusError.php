<?php

namespace App\Exceptions;

class TransferStatusError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Transfer expired or canceled',
        $errors = []
    ) {
        parent::__construct($message, 410, $errors);
    }
}
