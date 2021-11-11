<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'account_id' => $this->account_id,
            'coin' => $this->coin,
            'transaction' => $this->transaction,
            'address' => $this->address,
            'tag' => $this->tag,
            'amount' => $this->amount,
            'message' => $this->message,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at,
        ];
    }
}
