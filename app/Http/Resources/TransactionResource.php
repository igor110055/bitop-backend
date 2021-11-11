<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Transaction;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $coin = $this->account->coin;
        if ($this->type === Transaction::TYPE_TRANSFER_IN) {
            $user = data_get($this, 'transactable.src_user');
        } elseif ($this->type === Transaction::TYPE_TRANSFER_OUT) {
            $user = data_get($this, 'transactable.dst_user');
        } else {
            $user = null;
        }
        return [
            'id' => $this->id,
            'type' => $this->type,
            'account_id' => $this->account_id,
            'coin' => $coin,
            'amount' => $this->amount,
            'balance' => $this->balance,
            'transactable_type' => $this->transactable_type,
            'transactable_id' => $this->transactable_id,
            'user_id' => $this->when(isset($user), data_get($user, 'id')),
            'username' => $this->when(isset($user), data_get($user, 'username')),
            'message' => $this->message,
            'txid' => $this->when(isset($this->transaction), $this->transaction),
            'address' => $this->when(isset($this->address), $this->address),
            'tag' => $this->when(isset($this->tag), $this->tag),
            'withdrawal_status' => $this->when(isset($this->withdrawal_status), $this->withdrawal_status),
            'created_at' => $this->created_at,
        ];
    }
}
