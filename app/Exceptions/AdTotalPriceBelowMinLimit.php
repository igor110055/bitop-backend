<?php

namespace App\Exceptions;

class AdTotalPriceBelowMinLimit extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Advertisement value is below the minimum limit.',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
