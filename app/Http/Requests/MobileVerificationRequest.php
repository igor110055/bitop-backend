<?php

namespace App\Http\Requests;

class MobileVerificationRequest extends PublicRequest
{
    public function rules()
    {
        return [
            'mobile' => 'required|regex:/^[1-9]{1}[0-9]{5,14}$/',
        ];
    }
}
