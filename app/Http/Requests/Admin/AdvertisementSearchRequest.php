<?php

namespace App\Http\Requests\Admin;

use App\Models\Advertisement;

class AdvertisementSearchRequest extends SearchRequest
{
    public static function rules()
    {
        $rules = [
            'status' => 'required|in:'.implode(',', array_merge(Advertisement::STATUS, ['all'])),
            'is_express' => 'required|in:0,1,all',
        ];
        return array_merge(parent::rules(), $rules);
    }
}
