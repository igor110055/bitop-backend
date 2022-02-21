<?php

namespace App\Http\Requests;

class PreviewWithdrawalRequest extends PublicRequest
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
            'amount' => 'required|numeric|min:0',
            'address' => 'string|nullable',
        ];
    }
}
