<?php

namespace App\Http\Requests;

use App\Models\Advertisement;

class CoinPriceRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $types = Advertisement::$types;
        $coins = config('core.coin.all');
        $currencies = config('core.currency.all');

        return [
            'coin' => 'required|in:'.implode(",", $coins),
            'currency' => 'in:'.implode(",", $currencies),
            'action' => 'required|in:'.implode(",", $types),
            'amount' => 'numeric|min:0',
        ];
    }
}
