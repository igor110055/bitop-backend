<?php

namespace App\Http\Requests\Admin;

class AgencyCreateRequest extends AgencyUpdateRequest
{
    public static $rules = [
        'id' => 'required|string|min:6|max:36|alpha_dash|unique:agencies',
    ];

    public static function rules()
    {
        return array_merge(parent::rules(), static::$rules);
    }
}
