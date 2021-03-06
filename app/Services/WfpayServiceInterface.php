<?php

namespace App\Services;

use App\Models\{
    WfpayAccount,
    Wfpayment,
    Wftransfer,
};

interface WfpayServiceInterface
{
    public function setAccount(WfpayAccount $wf_pay_account);
    public function getOrder(Wfpayment $wfpayment);
    public function createOrder(
        WfpayAccount $wfpay_account,
        $id,
        $amount,
        $payment_method = 'bank',
        $real_name,
        $notify_url,
        $return_url,
        $force_matching = true
    );
    public function getTransfer(Wftransfer $wftransfer);
    public function createTransfer(
        WfpayAccount $wfpay_account,
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
    public function verifyRequest(WfpayAccount $wfpay_account, \Illuminate\Http\Request $request, $exception = true) : bool;
    public function verifySignature($content, $signature, $exception = false) : bool;
}
