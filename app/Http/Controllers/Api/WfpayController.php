<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Dec\Dec;
use Symfony\Component\HttpKernel\Exception\{
    NotFoundHttpException,
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Exceptions\{
    Core\BadRequestError,
    DuplicateRecordError,
    VendorException,
};

use App\Repos\Interfaces\{
    WfpaymentRepo,
};
use App\Services\{
    OrderServiceInterface,
    WfpayServiceInterface,
};
use App\Models\{
    Wfpayment,
    Limitation,
    Verification,
    UserLog,
    UserLock,
};

class WfpayController extends Controller
{
    public function __construct(
        WfpaymentRepo $WfpaymentRepo,
        OrderServiceInterface $OrderService,
        WfpayServiceInterface $WfpayService
    ) {
        parent::__construct();
        $this->WfpaymentRepo = $WfpaymentRepo;
        $this->OrderService = $OrderService;
        $this->WfpayService = $WfpayService;
        $this->middleware(
            'auth:api',
            ['only' => []]
        );
    }

    public function paymentCallback(Request $request, string $id)
    {
        Log::info("Wfpay paymentCallback request", $request->all());

        $payload = $request->input('data');
        $data = data_get($payload, 'order');
        $notify_type = data_get($payload, 'notify_type');

        if ((data_get($data, 'status') === Wfpayment::STATUS_COMPLETED) and ($notify_type !== 'trade_completed')) {
            Log::alert("paymentCallback. {$id} notify_type is trade_completed but status is not completed.");
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
}
