<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeResource extends JsonResource
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
            'email' => $this->email,
            'mobile' => $this->mobile,
            'name' => $this->name,
            'username' => $this->username,
            'nationality' => $this->nationality,
            'authentication_status' => $this->authentication_status,
            'created_at' => $this->created_at,
            'locale' => $this->preferred_locale,
            'trade_number' => $this->trade_number,
            'two_factor_auth' => ($this->two_factor_auth) ? true : false,
            'is_agent' => $this->when($this->is_agent, $this->is_agent),
        ];
    }
}
