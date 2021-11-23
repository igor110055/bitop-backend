<?php

namespace App\Http\Requests;

use App\Models\Advertisement;

class ExpressSettingsRequest extends PublicRequest
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
            'coin' => 'required|in:'.implode(",", $coins),
            'currency' => 'required|in:'.implode(",",$currencies),
        ];
    }
}
