<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
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
        $src_user = $this->src_user;
        $dst_user = $this->dst_user;

        if ($user->is($src_user)) {
            $side = 'src';
            $display_user = $dst_user;
        } elseif ($user->is($dst_user)) {
            $side = 'dst';
            $display_user = $src_user;
        }

        $response = [
            'id' => $this->id,
            'user_id' => $display_user->id,
            'username' => $display_user->username,
            'side' => $this->when(isset($side), $side),
            'coin' => $this->coin,
            'amount' => $this->amount,
            'message' => $this->message,
            'memo' => $side === 'src' ? $this->memo : null,
            'created_at' => $this->created_at,
            'confirmed_at' => $this->confirmed_at,
            'canceled_at' => $this->canceled_at,
            'expired_at' => $this->expired_at,
            'status' => $this->status,
        ];
        return $response;
    }
}
