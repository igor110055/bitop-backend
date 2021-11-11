<?php

namespace App\Http\Requests;

use App\Models\Advertisement;

class PreviewAdRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $types = Advertisement::$types;
        $coins = array_keys(config('coin'));
        $currencies = array_keys(config('currency'));

        return [
            'type' => 'required|in:'.implode(',', $types),
            'coin' => 'required|in:'.implode(',', $coins),
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:'.implode(',', $currencies),
            'unit_price' => 'required|numeric|min:0',
        ];
    }
}
