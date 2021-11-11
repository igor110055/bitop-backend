<?php

namespace App\Http\Requests\Admin;

use App\Models\Limitation;

class LimitationCreateRequest extends AdminRequest
{
    public static function rules()
    {
        $types = Limitation::TYPES;
        $coins = array_keys(config('coin'));
        return [
            'type' => 'required|in:'.implode(",", $types),
            'coin' => 'required|in:'.implode(",", $coins),
            'min' => 'required|numeric|min:0',
            'max' => 'required|numeric',
        ];
    }
}
