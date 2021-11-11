<?php

namespace App\Http\Requests;

class CurrencyRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $currencies = array_keys(config('currency'));
        return [
            'currency' => 'required|in:'.implode(",", $currencies),
        ];
    }

    public function validationData()
    {
        return array_merge($this->request->all(), [
            'currency' => $this->route('currency'),
        ]);
    }
}
