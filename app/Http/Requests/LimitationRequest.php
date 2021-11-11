<?php

namespace App\Http\Requests;

use App\Models\Limitation;

class LimitationRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $types = Limitation::TYPES;
        $coins = array_keys(config('coin'));

        return [
            'type' => 'required|in:'.implode(',', $types),
            'coin' => 'required|in:'.implode(',', $coins),
        ];
    }
}
