<?php

namespace App\Http\Requests;

class RecoverSecurityCodeRequest extends PublicRequest
{
    public function rules()
    {
        return [
            'verification_id' => 'required|string',
            'verification_code' => 'required|string',
            'security_code' => "required|confirmed|string|max:60",
        ];
    }
}
