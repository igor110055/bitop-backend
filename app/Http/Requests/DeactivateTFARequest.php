<?php

namespace App\Http\Requests;

class DeactivateTFARequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'verification_id' => 'required|string',
            'verification_code' => 'required|string',
            'security_code' => "required|string|max:60",
            'code' => 'required|string',
        ];
    }
}
