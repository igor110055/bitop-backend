<?php

namespace App\Http\Requests\Admin;

class GroupCreateRequest extends GroupUpdateRequest
{
    public function rules()
    {
        $coins_cap = array_keys(config('coin'));
        $coins_uncap = array_map(function ($coin) {
            return strtolower($coin);
        }, $coins_cap);

        $rule = [
            'id' => 'required|string|min:6|max:36|alpha_dash|unique:groups|not_in:'.implode(',', $coins_cap).'|not_in:'.implode(',', $coins_uncap).'|not_regex:/system/',
        ];

        return array_merge(parent::rules(), $rule);
    }
}
