<?php

namespace App\Http\Requests\Admin;

use App\Models\Transaction;

class AccountManipulationRequest extends AdminRequest
{
    public static function rules()
    {
        $types = [
            Transaction::TYPE_MANUAL_DEPOSIT,
            Transaction::TYPE_MANUAL_WITHDRAWAL,
        ];

        return [
            'type' => 'required|in:'.implode(",", $types),
            'amount' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:128',
            'message' => 'nullable|string|max:128',
        ];
    }
}
