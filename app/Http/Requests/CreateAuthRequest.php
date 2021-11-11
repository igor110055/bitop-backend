<?php

namespace App\Http\Requests;

use App\Rules\AvailableAuthFileId;

class CreateAuthRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string|max:128',
            'last_name' => 'required|string|max:128',
            'username' => 'required|regex:/^(?=.{3,24}$)(?:[a-zA-Z0-9]+\s?[a-zA-Z0-9]*\s?[a-zA-Z0-9]*\s?[a-zA-Z0-9]*)$/',
            'security_code' => 'required|string|max:256',
            'id_number' => 'required|string|max:256',
            'file_ids' => 'required|array',
            'file_ids.*' => ['required', new AvailableAuthFileId(auth()->user())],
        ];
    }
}
