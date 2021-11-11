<?php

namespace App\Http\Requests;

class ResetMobileRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mobile' => 'required|regex:/^[1-9]{1}[0-9]{5,14}$/',
            'verification_id' => 'required|string',
            'verification_code' => 'required|string',
            'security_code' => "required|confirmed|string|max:60",
        ];
    }
}
