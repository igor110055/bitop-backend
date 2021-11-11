<?php

namespace App\Http\Requests\Admin;

use App\Models\FeeSetting;

class FeeSettingRequest extends AdminRequest
{
    public static function rules()
    {
        $types = FeeSetting::RANGE_TYPES;
        $coins = config('core.coin.all');
        return [
            'type' => 'required|in:'.implode(",", $types),
            'coin' => 'required|in:'.implode(",", $coins),
            'applicable_id' => 'nullable|string',
            'ranges.*.unit' => 'required|in:'.implode(",", $coins).',%',
            'ranges.*.value' => 'required|numeric',
            'ranges.*.range_end' => 'nullable|numeric',
        ];
    }
}
