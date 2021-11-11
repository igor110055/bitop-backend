<?php

namespace App\Http\Requests;

class RegisterRequest extends PublicRequest
{
    public function rules()
    {
        $regex = config('core.users.password.regular_expression');
        $locales = config('core.locale.all');
        return [
            'nationality' => 'required|string|exists:iso3166s,alpha_2',
            'email' => 'required|email',
            'mobile' => 'required|regex:/^[1-9]{1}[0-9]{5,14}$/',
            'email_verification_code' => 'required|string',
            'mobile_verification_code' => 'required|string',
            'email_verification_id' => 'required|string',
            'mobile_verification_id' => 'required|string',
            'password' => "required|confirmed|string|regex:$regex",
            'invitation_code' => "nullable|string",
            'locale' => 'nullable|string|in:'.implode(',', $locales),
        ];
    }
}
