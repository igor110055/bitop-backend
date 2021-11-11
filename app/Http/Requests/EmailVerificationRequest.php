<?php

namespace App\Http\Requests;

class EmailVerificationRequest extends PublicRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email',
        ];
    }
}
