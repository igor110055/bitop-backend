<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Arr;

class Error extends HttpException
{
    public function __construct(
        string $message = 'Bad Request',
        int $status_code = 400,
        $errors = [],
        \Throwable $previous = null,
        $code = 0
    ) {
        $this->errors = [];
        if (is_array($errors)) {
            $this->errors = array_map(function($item) {
                return Arr::wrap($item);
            }, $errors);
        }
        parent::__construct($status_code, $message, $previous, [], $code);
    }

    public function errors()
    {
        return $this->errors;
    }
}
