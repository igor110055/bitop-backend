<?php

namespace App\Http\Requests;

use App\Rules\AvailableBankAccountId;

class TradeRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'action' => 'required|string|in:buy,sell',
            'advertisement_id' => 'required|exists:advertisements,id',
            'amount' => 'required|numeric|min:0',
            'security_code' => "required|string|max:60",
        ];
    }

    public function withValidator($validator)
    {
        $this->sometimes(
            $validator, [
                'payables' => 'required',
                'payables.bank_account' => 'required',
                'payables.bank_account.*' => ['required', new AvailableBankAccountId(auth()->user())],
            ],
            function($input) {
                return $input->action === 'sell';
            });
    }

    public function sometimes($validator, array $rules, callable $condition)
    {
        foreach ($rules as $name => $rule) {
            $validator->sometimes($name, $rule, $condition);
        }
    }
}
