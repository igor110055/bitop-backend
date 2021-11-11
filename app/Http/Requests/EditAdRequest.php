<?php

namespace App\Http\Requests;

use App\Models\Advertisement;
use App\Rules\AvailableBankAccountId;

class EditAdRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'min_trades' => 'required|numeric|min:0',
            'terms' => 'nullable|string',
            'message' => 'nullable|string',
            'payables' => 'required',
            'payables.bank_account' => 'array',
            'payables.bank_account.*' => [new AvailableBankAccountId(auth()->user())],
            'security_code' => "required|string|max:60",
            'min_limit' => 'required|numeric',
            'max_limit' => 'required|numeric|gte:min_limit',
            'payment_window' => 'required|integer',
        ];
    }

    public function withValidator($validator)
    {
        $currency_rules = config('currency');
        foreach ($currency_rules as $currency => $rule) {
            $validator->sometimes('min_limit', 'required|numeric|min:'.$rule['min_limit'], function ($input) use ($currency) {
                return $input->currency === $currency;
            });
        }

        $validator->sometimes('payables.bank_account', 'required', function ($input) {
            return ($input->type === Advertisement::TYPE_SELL) or !(auth()->user()->is_agent);
        });
    }
}
