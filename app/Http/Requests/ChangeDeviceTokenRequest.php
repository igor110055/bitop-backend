<?php

namespace App\Http\Requests;

class ChangeDeviceTokenRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'active' => 'required|boolean',
        ];
    }
}
