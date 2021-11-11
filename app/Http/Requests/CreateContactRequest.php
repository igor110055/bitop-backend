<?php

namespace App\Http\Requests;

class CreateContactRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:20',
            'contact_id' => 'required|string|size:14',
            'security_code' => "required|string|max:60",
        ];
    }
}
