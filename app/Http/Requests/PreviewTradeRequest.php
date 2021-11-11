<?php

namespace App\Http\Requests;

class PreviewTradeRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'price' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'advertisement_id' => 'required|exists:advertisements,id',
        ];
    }
}
