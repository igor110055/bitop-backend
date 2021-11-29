<?php

namespace App\Services;

use App\Models\{
    Advertisement,
    Order,
    User,
};

interface OrderServiceInterface
{
    public function make(User $user, Advertisement $advertisement, $amount, array $payables);
    public function makeExpress(
        User $user,
        Advertisement $advertisement,
        $amount = null,
        $total = null,
        $payment_method = null,
        $payables = null
    );
    public function claim(
        $order_id,
        $payment_src_type,
        $payment_src_id,
        $payment_dst_type,
        $payment_dst_id
    );
    public function confirm($order_id);
    public function getProfitUnitPrice(Order $order);
    public function calculateProfitUnitPrice(
        $dst_user,
        $src_user,
        $coin,
        $coin_amount,
        $coin_unit_price,
        $currency,
        $currency_amount
    );
    public function cancel(
        User $user,
        $order_id,
        $action = User::class
    );
    public function revoke(
        User $user,
        $order_id
    );
    public function updateWfpaymentAndOrder($wfpayment_id, $data);
}
