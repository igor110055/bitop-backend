<?php

namespace App\Http\Requests;

use App\Models\BankAccount;
use App\Rules\{
    AvailableBankId,
    SupportedCurrency,
};

class CreateBankAccountRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $prime_nationalities = config('core')['nationality'];
        $types = BankAccount::$types;

        return [
            'bank_id' => 'required|string|exists:banks,id',
            'type' => 'required|string|in:'.implode(',', $types),
            'account' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
            ],
            'name' => 'required|string|min:1',
            'bank_city_name' => 'required|string|min:1',
            'bank_province_name' => 'required|string|min:1',
        ];
    }
}
