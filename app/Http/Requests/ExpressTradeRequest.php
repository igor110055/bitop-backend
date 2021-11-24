<?php

namespace App\Http\Requests;

use App\Models\Wfpayment;
use App\Rules\AvailableBankAccountId;

class ExpressTradeRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $paymeny_methods = Wfpayment::$methods;

        return [
            'action' => 'required|string|in:buy,sell',
            'advertisement_id' => 'required|exists:advertisements,id',
            'payment_method' => 'required_if:action,buy|in:'.implode(',', $paymeny_methods),
            'total' => 'required_without:amount|numeric|min:0',
            'amount' => 'required_without:total|numeric|min:0',
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
