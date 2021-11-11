<?php

namespace App\Exceptions;

class MinimumTradesError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'User trade number under minimum trades.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
