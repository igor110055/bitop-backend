<?php

namespace App\Http\Requests\Admin;

use App\Models\AssetTransaction;

class AssetManipulationRequest extends AdminRequest
{
    public static function rules()
    {
        $types = [
            AssetTransaction::TYPE_MANUAL_DEPOSIT,
            AssetTransaction::TYPE_MANUAL_WITHDRAWAL,
        ];

        return [
            'type' => 'required|in:'.implode(",", $types),
            'amount' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:128',
        ];
    }
}
