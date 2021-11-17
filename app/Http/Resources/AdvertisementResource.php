<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = auth()->user();

        $is_recommended = isset($this->is_recommended) ? $this->is_recommended : false;

        return [
            'id' => $this->id,
            'is_express' => $this->is_express,
            'user_id' => $this->user_id,
            'username' => $this->owner->username,
            'type' => $this->type,
            'status' => $this->status,
            'coin' => $this->coin,
            'amount' => $this->remaining_amount,
            'currency' => $this->currency,
            'unit_price' => $this->unit_price,
            'fee' => $this->when($user->is($this->owner), $this->remaining_fee),
            'min_trades' => $this->min_trades,
            'min_limit' => $this->min_limit,
            'max_limit' => $this->max_limit,
            'payment_window' => $this->payment_window,
            'terms' => $this->terms,
            'message' => $this->when($user->is($this->owner), $this->message),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'self' => $this->self,
            'payables' => [
                'bank_account' => VagueBankAccountResource::collection($this->bank_accounts),
            ],
            'is_recommended' => $this->when($is_recommended, true),
            'reference_id' => $this->reference ? $this->reference->id : null,
            'owner' => [
                'id' => $this->user_id,
                'username' => $this->owner->username,
                'complete_rate' => $this->owner->complete_rate,
                'trade_number' => $this->owner->trade_number,
            ],
        ];
    }
}
