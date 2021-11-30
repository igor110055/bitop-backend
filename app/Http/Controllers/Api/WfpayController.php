<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Exceptions\{
    Core\BadRequestError,
};

use App\Repos\Interfaces\{
    WfpaymentRepo,
    WftransferRepo,
};
use App\Services\{
    OrderServiceInterface,
    WfpayServiceInterface,
};
use App\Models\{
    Wfpayment,
    Wftransfer,
};

class WfpayController extends Controller
{
    public function __construct(
        WfpaymentRepo $WfpaymentRepo,
        WftransferRepo $WftransferRepo,
        OrderServiceInterface $OrderService,
        WfpayServiceInterface $WfpayService
    ) {
        parent::__construct();
        $this->WfpaymentRepo = $WfpaymentRepo;
        $this->WftransferRepo = $WftransferRepo;
        $this->OrderService = $OrderService;
        $this->WfpayService = $WfpayService;
    }

    public function paymentCallback(Request $request, string $id)
    {
        $content = $request->getContent();
        Log::info("Wfpay paymentCallback request", $request->all());

        $wfpayment = $this->WfpaymentRepo->findOrFail($id);

        $payload = $request->input('data');
        $payload = json_decode($payload, true);
        $data = data_get($payload, 'order');
        $notify_type = data_get($payload, 'notify_type');
        $merchant_order_id = data_get($data, 'merchant_order_id');

        if ($merchant_order_id !== $id) {
            Log::alert("paymentCallback. Wrong id: callback of wfpayment {$id}, but {$merchant_order_id} received.");
            return response(null, 400);
        }

        $this->WfpaymentRepo->update($wfpayment, ['callback_response' => $request->all()]);

        if ((data_get($data, 'status') === Wfpayment::STATUS_COMPLETED) and ($notify_type !== 'trade_completed')) {
            Log::alert("paymentCallback. {$id} status is completed but notify_type is not trade_completed.");
            return response(null, 400);
        }

        try {
            $this->WfpayService->verifyRequest($request);
            $this->OrderService->updateWfpaymentAndOrder($id, $data);
        } catch (\Throwable $e) {
            Log::alert("paymentCallback. Throwable cateched: ". $e->getMessage());
            return response(null, 400);
        }
        return response(null, 200);
    }

    public function transferCallback(Request $request, string $id)
    {
        $content = $request->getContent();
        Log::info("Wfpay transferCallback request", $request->all());

        $wftransfer = $this->WftransferRepo->findOrFail($id);

        $payload = $request->input('data');
        $payload = json_decode($payload, true);
        $data = data_get($payload, 'order');
        $notify_type = data_get($payload, 'notify_type');
        $merchant_order_id = data_get($data, 'merchant_order_id');

        if ($merchant_order_id !== $id) {
            Log::alert("transferCallback. Wrong id: callback of wftransfer {$id}, but {$merchant_order_id} received.");
            return response(null, 400);
        }

        $this->WftransferRepo->update($wftransfer, ['callback_response' => $request->all()]);

        if ((data_get($data, 'status') === Wftransfer::STATUS_COMPLETED) and ($notify_type !== 'payment_transfer_completed')) {
            Log::alert("transferCallback. {$id} status is completed but notify_type is not payment_transfer_completed");
            return response(null, 400);
        }

        try {
            $this->WfpayService->verifyRequest($request);
            $this->OrderService->updateWftransferAndOrder($id, $data);
        } catch (\Throwable $e) {
            Log::alert("transferCallback. Throwable cateched: ". $e->getMessage());
            return response(null, 400);
        }
        return response(null, 200);
    }
}
