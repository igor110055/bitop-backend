<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'authentication_status' => $this->authentication_status,
            'created_at' => $this->created_at,
            'complete_rate' => $this->complete_rate,
            'trade_number' => (string)$this->trade_number,
            'average_pay_time' => $this->average_pay_time,
            'average_release_time' => $this->average_release_time,
        ];
    }
}
