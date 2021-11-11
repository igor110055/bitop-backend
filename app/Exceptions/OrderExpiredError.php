<?php

namespace App\Exceptions;

class OrderExpiredError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Order pay time expired.',
        $errors = []
    ) {
        parent::__construct($message, 410, $errors);
    }
}
