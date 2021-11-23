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
        # TODO: verify signature
        # $this->WfpayService->verifyRequest($request);

        $wfpayment = $this->WfpaymentRepo->findOrFail($id);
        $values = $request->all();
        return $wfpayment->return_url;
        try {
            # TODO: check & complete order
            //$this->WfpayService->handlePaymantCallback($values);
            //$this->OrderService->handleWfpaymantCallback($wfpayment, $values);
        } catch (DuplicateRecordError $e) {
            Log::error("paymentCallback. Duplicate payment {$wfpayment->id} callback received.");
        } catch (VendorException $e) {
            Log::alert("paymentCallback. VendorException: ". $e->getMessage());
            return response(null, 400);
        }
        return response(null, 200);
    }
}
