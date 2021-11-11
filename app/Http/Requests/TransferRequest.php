<?php

namespace App\Http\Requests;

class TransferRequest extends PublicRequest
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
            'dst_user_id' => 'required|numeric|digits:14',
            'coin' => 'required|in:'.implode(',', $coins),
            'amount' => 'required|numeric|min:0',
            'message' => 'nullable|string|max:128',
            'memo' => 'nullable|string|max:128',
            'security_code' => "required|string|max:60",
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('code', 'required|string', function ($input) {
            return auth()->user()->two_factor_auth;
        });
    }
}
