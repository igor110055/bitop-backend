<?php

namespace App\Http\Requests;

class DuplicateWithdrawalRequest extends PublicRequest
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
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('tag', 'required|string', function ($input) {
            return in_array($input->coin, config('core.coin.require_tag'));
        });
    }
}
