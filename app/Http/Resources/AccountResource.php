<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'coin' => $this->coin,
            'balance' => $this->balance,
            'locked_balance' => $this->locked_balance,
            'available_balance' => $this->available_balance,
            'price' => $this->price,
            'address' => $this->address,
            'tag' => $this->tag,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
