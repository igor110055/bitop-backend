<?php

namespace App\Http\Requests;

class ResetPasswordRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $regex = config('core.users.password.regular_expression');
        return [
            'old_password' => "required|string|regex:$regex",
            'password' => "required|confirmed|string|regex:$regex",
        ];
    }
}
