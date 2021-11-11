<?php

namespace App\Http\Requests;

class RecoverPasswordRequest extends PublicRequest
{
    public function rules()
    {
        $regex = config('core.users.password.regular_expression');
        return [
            'email' => 'required|email',
            'verification_id' => 'required|string',
            'verification_code' => 'required|string',
            'password' => "required|confirmed|string|regex:$regex",
        ];
    }
}
