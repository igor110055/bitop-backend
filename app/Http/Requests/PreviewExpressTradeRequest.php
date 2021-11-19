<?php

namespace App\Http\Requests;

use App\Models\Advertisement;

class PreviewExpressTradeRequest extends PublicRequest
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
            'action' => 'required|in:'.implode(",", $types),
            'coin' => 'required_without:user_id|in:'.implode(",", $coins),
            'currency' => 'nullable|in:'.implode(",",$currencies),
            'total' => 'required_without:amount|numeric|min:0',
            'amount' => 'required_without:total|numeric|min:0',
        ];
    }
}
