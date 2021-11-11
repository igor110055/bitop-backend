<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VagueBankAccountResource extends JsonResource
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
            'type' => $this->type,
            'bank' => new BankResource($this->bank),
            'bank_branch_name' => $this->bank_branch_name,
            'bank_branch_phonetic_name' => $this->bank_branch_phonetic_name,
            'bank' => $this->bank,
        ];
    }
}
