<?php

namespace App\Http\Requests\Admin;

class SearchRequest extends AdminRequest
{
    public static function rules()
    {
        return [
            'search.value' => 'string|nullable',
            'from' => 'date',
            'to' => 'date',
            'coin' => 'string|nullable',
            'order' => 'array|nullable',
        ];
    }
}
