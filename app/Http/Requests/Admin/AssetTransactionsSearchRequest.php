<?php

namespace App\Http\Requests\Admin;

class AssetTransactionsSearchRequest extends SearchRequest
{
    public static $rules = [
        'id' => 'required|string',
    ];

    public static function rules()
    {
        return array_merge(parent::rules(), static::$rules);
    }
}
