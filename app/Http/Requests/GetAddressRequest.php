<?php

namespace App\Http\Requests;

class GetAddressRequest extends PublicRequest
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
            'coin' => 'nullable|in:'.implode(',', $coins),
        ];
    }
}
