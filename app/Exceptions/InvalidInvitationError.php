<?php

namespace App\Exceptions;

class InvalidInvitationError extends \App\Exceptions\Error
{
    public function __construct(
        string $message = 'Invalid invitation code',
        $errors = []
    ) {
        parent::__construct($message, 400, $errors);
    }
}
