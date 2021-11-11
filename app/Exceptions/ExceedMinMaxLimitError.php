<?php

namespace App\Exceptions;

class ExceedMinMaxLimitError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Requested price is not in the interval of min_limit and max_limit',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
