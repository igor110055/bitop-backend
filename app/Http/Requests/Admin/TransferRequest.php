<?php

namespace App\Http\Requests\Admin;

class TransferRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $coins = config('core.coin.all');
        return [
            'dst_user_id' => 'required|string|exists:users,id',
            'coin' => 'required|in:'.implode(",", $coins),
            'amount' => 'required|numeric|min:0',
            'note' => 'required|string|max:128',
            'src_message' => 'nullable|string|max:128',
            'dst_message' => 'nullable|string|max:128',
        ];
    }
}
