<?php

namespace App\Http\Requests;

class CreateWithdrawalRequest extends PublicRequest
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
            'coin' => 'required|in:'.implode(",", $coins),
            'address' => 'required|string',
            'tag' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'security_code' => "required|string|max:60",
            'message' => 'nullable|string|max:128',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('tag', 'required|string', function ($input) {
            return in_array($input->coin, config('core.coin.require_tag'));
        });

        $validator->sometimes('code', 'required|string', function ($input) {
            return auth()->user()->two_factor_auth;
        });
    }
}
