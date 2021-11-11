<?php

namespace App\Http\Requests;

class UserUpdateRequest extends PublicRequest
{
    public function rules()
    {
        $locales = config('core.locale.all');
        return [
            'locale' => 'nullable|string|in:'.implode(',', $locales),
        ];
    }
}
