<?php

namespace App\Http\Requests;

class TransferListRequest extends PublicRequest
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
            'coin' => 'nullable|in:'.implode(',', $coins),
            'side' => 'required|string|in:'.implode(',', ['src', 'dst']),
            'start' => 'required|integer',
            'end' => 'required|integer',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
            'user_id' => 'nullable|numeric',
        ];
    }
}
