<?php

namespace App\Exceptions;

use App\Exceptions\Exception;

class ServiceUnavailableError extends Exception
{
    public function __construct(
        $message = self::class,
        int $status_code = 503,
        $errors = [],
        \Throwable $previous = null,
        $code = 0
    ) {
        parent::__construct($message, $status_code, $errors, $previous, $code);
    }
}
