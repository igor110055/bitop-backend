<?php

namespace App\Http\Requests;

use App\Models\Advertisement;

class GetAdRequest extends PublicRequest
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
        $nationalities = array_keys(config('core.nationality'));

        return [
            'user_id' => 'nullable',
            'action' => 'required|in:'.implode(",", $types),
            'coin' => 'required_without:user_id|in:'.implode(",", $coins),
            'currency' => 'nullable|in:'.implode(",",$currencies),
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ];
    }
}
