<?php

namespace App\Http\Requests;

class PasswordVerificationRequest extends PublicRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email',
        ];
    }
}
