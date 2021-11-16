<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
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
            'bank_id' => $this->bank_id,
            'currency' => $this->currency,
            'account' => $this->account,
            'type' => $this->type,
            'name' => $this->name,
            'bank_province_name' => $this->bank_province_name,
            'bank_city_name' => $this->bank_city_name,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'bank' => new BankResource($this->bank),
        ];
    }
}
