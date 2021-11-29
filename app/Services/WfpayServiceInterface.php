<?php

namespace App\Services;

interface WfpayServiceInterface
{
    public function getOrder($id);
    public function createOrder(
        $id,
        $amount,
        $payment_method = 'bank',
        $real_name,
        $notify_url,
        $return_url,
        $force_matching = true
    );
    public function createTranfer(
        $id,
        $amount,
        $notify_url,
        $bank_name,
        $bank_province_name,
        $bank_city_name,
        $bank_account_no,
        $bank_account_type,
        $bank_account_name
    );
    public function rematch($id);
    public function verifyRequest(\Illuminate\Http\Request $request, $exception = true) : bool;
    public function verifySignature($content, $signature, $exception = false) : bool;
}
