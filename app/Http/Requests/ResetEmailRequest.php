<?php

namespace App\Http\Requests;

class ResetEmailRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'verification_id' => 'required|string',
            'verification_code' => 'required|string',
            'security_code' => "required|confirmed|string|max:60",
        ];
    }
}
