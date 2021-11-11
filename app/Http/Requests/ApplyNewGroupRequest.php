<?php

namespace App\Http\Requests;

class ApplyNewGroupRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'group_name' => 'required|regex:/^[[:alnum:]]+$/',
            'description' => 'required|string',
        ];
    }
}
