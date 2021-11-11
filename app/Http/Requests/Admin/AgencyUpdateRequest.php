<?php

namespace App\Http\Requests\Admin;

class AgencyUpdateRequest extends AdminRequest
{
    public static function rules()
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:64', 'not_regex:/system/'],
        ];
    }
}
