<?php

namespace App\Http\Requests;

class LoginRequest extends PublicRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => "required|string|min:1",
        ];
    }
}
