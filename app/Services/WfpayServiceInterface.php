<?php

namespace App\Services;

interface WfpayServiceInterface
{
    public function getOrder($id);
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
    public function verifyRequest(\Illuminate\Http\Request $request, $exception = true) : bool;
    public function verifySignature($content, $signature, $exception = false) : bool;
}
