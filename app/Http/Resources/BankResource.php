<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankResource extends JsonResource
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
            'nationality' => $this->nationality,
            'name' => $this->name,
            'phonetic_name' => $this->phonetic_name,
            'foreign_name' => $this->foreign_name,
            'swift_id' => $this->swift_id,
            'local_code' => $this->local_code,
        ];
    }
}
