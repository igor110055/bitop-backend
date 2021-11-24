<?php

namespace App\Services;

interface WfpayServiceInterface
{
    public function createPayment(
        $id,
        $amount,
        $payment_method = 'bank',
        $real_name,
        $notify_url,
        $return_url,
        $force_matching = true
    );

    public function rematch($id);
}
