<?php

namespace App\Exceptions;

use App\Exceptions\Exception;

class VendorException extends Exception
{
    public function __construct(
        $message = self::class,
        int $status_code = 500,
        $errors = [],
        \Throwable $previous = null,
        $code = 0
    ) {
        parent::__construct($message, $status_code, $errors, $previous, $code);
    }
}
