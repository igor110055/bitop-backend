<?php

namespace App\Http\Requests\Admin;

use App\Models\Authentication;

class VerifyUserRequest extends AdminRequest
{
    public static function rules()
    {
        $reject_reasons = Authentication::REASONS;

        return [
            'action' => 'required|in:approve,reject',
            'reasons' => 'array|nullable',
            'reasons.*' => 'string|in:'.implode(',', $reject_reasons),
            'other_reason' => 'string|nullable|max:255',
        ];
    }
}
