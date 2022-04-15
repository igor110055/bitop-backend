<?php

namespace App\Http\Requests\Admin;

class MerchantUpdateRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:64|not_regex:/system/',
        ];
    }
}
