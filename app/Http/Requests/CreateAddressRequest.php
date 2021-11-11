<?php

namespace App\Http\Requests;

class CreateAddressRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $coins = array_keys(config('coin'));
        return [
            'label' => 'required|string|max:20',
            'coin' => 'required|in:'.implode(',', $coins),
            'address' => 'required|string',
            'tag' => 'nullable|string',
            'security_code' => "required|string|max:60",
        ];
    }
}
