<?php

namespace App\Http\Requests;

class ActivateTFARequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'security_code' => 'required|string|max:60',
            'code' => 'required|string',
        ];
    }
}
