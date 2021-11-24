<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\{
    Advertisement,
    Order,
    Wfpayment,
};

class OrderResource extends JsonResource
{
    protected $event;

    public function withEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    public function withPayment($wfpayment)
    {
        if (!$this->is_express) {
            return $this;
        }
        $user = auth()->user();
        if ($this->advertisement->type === Advertisement::TYPE_BUY) {
            return $this;
        } else {
            if ($user->is($this->src_user)) {
                return $this;
            }
        }
        $result = [
            'is_valid' => false,
        ];
        if (is_null($wfpayment) or $this->status !== Order::STATUS_PROCESSING) {
            $this->payment = $result;
            return $this;
        }
        $valid_status = [Wfpayment::STATUS_INIT, Wfpayment::STATUS_PENDINT_PAYMENT, Wfpayment::STATUS_PENDINT_ALLOCATION];
        if (in_array($wfpayment->status, $valid_status)) {
            $result['is_valid'] = true;
            if ($wfpayment->payment_method === Wfpayment::METHOD_BANK && !is_null($wfpayment->payment_info)) {
                $result['bank_account'] = $wfpayment->payment_info;
            } else {
                $result['payment_url'] = $wfpayment->payment_url;
            }
        }
        $this->payment = $result;
        return $this;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $payment_types_map = Order::PAYMENT_TYPES_MAP;
        $user = auth()->user();
        $src_user = $this->src_user;
        $dst_user = $this->dst_user;

        if ($user->is($src_user)) {
            $role = 'src_user';
        } elseif ($user->is($dst_user)) {
            $role = 'dst_user';
        }

        $order = [
            'id' => $this->id,
            'is_express' => $this->is_express,
            'src_user_id' => $src_user->id,
            'src_username' => $src_user->username,
            'dst_user_id' => $dst_user->id,
            'dst_username' => $dst_user->username,
            'role' => $this->when(isset($role), $role),
            'status' => $this->status,
            'coin' => $this->coin,
            'amount' => $this->amount,
            'fee' => $this->when($user->is($src_user), $this->fee),
            'currency' => $this->currency,
            'total' => $this->total,
            'unit_price' => $this->unit_price,
            'terms' => $this->terms,
            'message' => $this->message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expired_at' => $this->expired_at,
            'canceled_at' => $this->when($this->canceled_at, $this->canceled_at),
            'claimed_at' => $this->when($this->claimed_at, $this->claimed_at),
            'revoked_at' => $this->when($this->revoked_at, $this->revoked_at),
            'completed_at' => $this->when($this->completed_at, $this->completed_at),
            'payables' => [
                Order::PAYABLE_BANK_ACCOUNT => BankAccountResource::collection($this->bank_accounts),
            ],
            'event' => $this->when($this->event, $this->event),
            'payment' => $this->when($this->payment, $this->payment),
        ];


        if ($this->payment_src) {
            $pay_src_type = $payment_types_map[$this->payment_src_type];
            $order['payment_src_type'] = $pay_src_type;
            $order['payment_src_id'] = $this->payment_src_id;
            if ($order['payment_src_type'] === Order::PAYABLE_BANK_ACCOUNT) {
                $order['payment_src'] = [$pay_src_type => new BankAccountResource($this->payment_src)];
            } else {
                $order['payment_src'] = [$pay_src_type => $this->payment_src];
            }
        }

        if ($this->payment_dst) {
            $pay_dst_type = $payment_types_map[$this->payment_dst_type];
            $order['payment_dst_type'] = $pay_dst_type;
            $order['payment_dst_id'] = $this->payment_dst_id;
            if ($order['payment_dst_type'] === Order::PAYABLE_BANK_ACCOUNT) {
                $order['payment_dst'] = [$pay_dst_type => new BankAccountResource($this->payment_dst)];
            } else {
                $order['payment_dst'] = [$pay_dst_type => $this->payment_dst];
            }
        }

        return $order;
    }
}
