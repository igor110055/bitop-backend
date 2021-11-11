<?php

namespace App\Http\Requests;

class CoinRequest extends PublicRequest
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
        ];
    }

    public function validationData()
    {
        return array_merge($this->request->all(), [
            'coin' => $this->route('coin'),
        ]);
    }
}
