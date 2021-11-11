<?php

namespace App\Http\Requests;

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

        return [
            'nationality' => 'required|in:'.implode(",", array_keys($prime_nationalities)),
            'account' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
            ],
            'name' => 'required|string|min:1',
            'phonetic_name' => 'nullable|string|min:1',
            'currency' => 'required|array',
            'bank_branch_name' => 'nullable|string|min:1',
            'bank_branch_phonetic_name' => 'nullable|string|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $prime_nationalities = config('core')['nationality'];

        $currency = [];
        foreach ($prime_nationalities as $nationality => $value) {
            $bank["$nationality"] = new AvailableBankId("$nationality");
            $currency["$nationality"] = new SupportedCurrency("$nationality");
        }
        extract($currency, EXTR_PREFIX_ALL, "currency");
        $currencyTW = ['currency.*' => [$currency_TW]];
        $currencyHK = ['currency.*' => [$currency_HK]];
        $currencyCN = ['currency.*' => [$currency_CN]];

        $with_phonetic_name = ['phonetic_name' => 'required|string|min:1'];

        extract($bank, EXTR_PREFIX_ALL, "bank_id");
        $check_bank_TW = ['bank_id' => [$bank_id_TW]];
        $check_bank_HK = ['bank_id' => [$bank_id_HK]];
        $check_bank_CN = ['bank_id' => [$bank_id_CN]];

        # TW
        $this->sometimes(
            $validator,
            $currencyTW + $with_phonetic_name + $check_bank_TW,
            function($input) {
                return $input->nationality === 'TW';
            });

        # HK
        $this->sometimes(
            $validator,
            $currencyHK + $check_bank_HK,
            function($input) {
                return $input->nationality === 'HK';
            });

        # CN
        $this->sometimes(
            $validator,
            $currencyCN + $with_phonetic_name + $check_bank_CN,
            function($input) {
                return $input->nationality === 'CN';
            });
    }

    public function sometimes($validator, array $rules, callable $condition)
    {
        foreach ($rules as $name => $rule) {
            $validator->sometimes($name, $rule, $condition);
        }
    }
}
