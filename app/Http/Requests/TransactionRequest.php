<?php

namespace App\Http\Requests;

class TransactionRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $coins = array_keys(config('coin'));

        return [
            'coin' => 'required|in:'.implode(",", $coins),
            'start' => 'required|integer',
            'end' => 'required|integer',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ];
    }
}
