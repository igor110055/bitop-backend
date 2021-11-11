<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'coin' => $this->coin,
            'network' => $this->network,
            'label' => $this->label,
            'address' => $this->address,
            'tag' => $this->tag,
            'created_at' => $this->created_at,
        ];
    }
}
