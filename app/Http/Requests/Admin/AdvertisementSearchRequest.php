<?php

namespace App\Http\Requests\Admin;

use App\Models\Advertisement;

class AdvertisementSearchRequest extends AdminRequest
{
    public static function rules()
    {
        return [
            'status' => 'required|in:'.implode(',', array_merge(Advertisement::STATUS, ['all'])),
            'from' => 'date|nullable',
            'to' => 'date|nullable',
            'search.value' => 'string|nullable',
        ];
    }
}
