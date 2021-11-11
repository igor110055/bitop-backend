<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Traits\{
    SecurityCodeTrait,
    ListQueryTrait,
};
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
};
use App\Exceptions\{
    Core\BadRequestError,
    Core\UnknownError,
    Auth\WrongSecurityCodeError,
    Verification\WrongCodeError,
    UnavailableStatusError,
};
use App\Http\Resources\{
    OrderResource,
    VerificationResource,
};
use App\Http\Requests\{
    OrderListRequest,
    ClaimOrderRequest,
    ConfirmOrderRequest,
    PreviewTradeRequest,
    TradeRequest,
};
use App\Models\{
    Order,
    Verification,
    UserLog,
    Advertisement,
    FeeSetting,
};
use App\Notifications\{
    OrderConfirmation,
    OrderCanceledNotification,
    OrderCompletedNotification,
};
use App\Repos\Interfaces\{
    UserRepo,
    OrderRepo,
    VerificationRepo,
    AdvertisementRepo,
};
use App\Services\{
    OrderServiceInterface,
    FeeServiceInterface,
    ExchangeServiceInterface,
};
use App\Jobs\Fcm\{
    OrderCanceledNotification as FcmOrderCanceledNotification,
    OrderCompletedNotification as FcmOrderCompletedNotification,
};

class OrderController extends AuthenticatedController
{
    use SecurityCodeTrait, ListQueryTrait;

    public function __construct(
        UserRepo $UserRepo,
        OrderRepo $OrderRepo,
        AdvertisementRepo $AdvertisementRepo,
        VerificationRepo $VerificationRepo,
        OrderServiceInterface $OrderService,
        FeeServiceInterface $FeeService,
        ExchangeServiceInterface $ExchangeService
    ) {
        parent::__construct();
        $this->UserRepo = $UserRepo;
        $this->OrderRepo = $OrderRepo;
        $this->AdvertisementRepo = $AdvertisementRepo;
        $this->VerificationRepo = $VerificationRepo;
        $this->OrderService = $OrderService;
        $this->FeeService = $FeeService;
        $this->ExchangeService = $ExchangeService;
    }

    public function index(OrderListRequest $request)
    {
        $values = $request->validated();
        
        $result = $this->OrderRepo->getUserOrders(
            auth()->user(),
            data_get($values, 'status'),
            $this->inputDateTime('start'),
            $this->inputDateTime('end'),
            $this->inputLimit(),
            $this->inputOffset()
        );

        return $this->paginationResponse(
            OrderResource::collection($result['data']),
            $result['filtered'],
            $result['total']
        );
    }

    public function show(string $id)
    {
        $order = $this->OrderRepo
            ->findOrFail($id);
        $this->checkAuthorization($order);
        return (new OrderResource($order))->withEvent($order->current_timeline);
    }

    public function previewTrade(PreviewTradeRequest $request)
    {
        $input = $request->validated();
        $user = auth()->user();
        $advertisement = $this->AdvertisementRepo->findOrFail($input['advertisement_id']);
        if ($advertisement->status !== Advertisement::STATUS_AVAILABLE) {
            throw new UnavailableStatusError('Advertisement is not available.');
        }
        $input['amount'] = trim_redundant_decimal($input['amount'], $advertisement->coin);


        $normalized = $this->ExchangeService->calculateCoinPrice(
            $advertisement->coin,
            $input['amount'],
            $advertisement->unit_price,
            $advertisement->currency
        );

        $result = [
            'coin' => $advertisement->coin,
            'currency' => $advertisement->currency,
            'amount' => $normalized['amount'],
            'unit_price' => $normalized['unit_price'],
            'price' => $normalized['price'],
        ];

        if ($advertisement->type === Advertisement::TYPE_BUY) {
            $fee = $this->FeeService->getFee(
                FeeSetting::TYPE_ORDER,
                $user, #src_user
                $advertisement->coin,
                $normalized['amount']
            );
            $result['fee'] = $fee['amount'];

            if (data_get($fee, 'fee_setting')) {
                if (data_get($fee, 'fee_setting.unit') !== '%') {
                    throw new BadRequestError('Fee setting error');
                }
                $result['fee_percentage'] = trim_zeros(data_get($fee, 'fee_setting.value', '0'));
            } else {
                $result['fee_percentage'] = '0';
            }
        }

        // calculate profit
        /* agent feature is not activated now 
            if ($user->is_agent and ($advertisement->type === Advertisement::TYPE_SELL)) {
            $profit = $this->OrderService
                ->calculateProfitUnitPrice(
                    $user,
                    $advertisement->owner,
                    $advertisement->coin,
                    $advertisement->amount,
                    $advertisement->unit_price,
                    $advertisement->currency,
                    $normalized['price']
                );
            $result['profit'] = $profit['profit'];
        } */
        return $result;
    }

    public function trade(TradeRequest $request)
    {
        $user = auth()->user();
        $input = $request->validated();
        $this->checkSecurityCode($user, $input['security_code']);
        $advertisement = $this->AdvertisementRepo->findOrFail($input['advertisement_id']);
        $input['amount'] = trim_redundant_decimal($input['amount'], $advertisement->coin);

        if ($input['action'] === 'buy') {
            if ($advertisement->type !== Advertisement::TYPE_SELL) {
                throw new BadRequestError;
            }
            $order = $this->OrderService
                ->make(
                    $user,
                    $advertisement,
                    $input['amount'],
                    []
                );
        } elseif ($input['action'] === 'sell') {
            if ($advertisement->type !== Advertisement::TYPE_BUY) {
                throw new BadRequestError;
            }
            $order = $this->OrderService
                ->make(
                    $user,
                    $advertisement,
                    $input['amount'],
                    data_get($input, 'payables', [])
                );
        }

        user_log(UserLog::ORDER_CREATE, ['order_id' => $order->id], request());
        return response()->json(
            new OrderResource($order),
            201
        );
    }

    public function claim(ClaimOrderRequest $request, string $id)
    {
        $user = auth()->user();
        $input = $request->validated();

        $order = $this->OrderService->claim(
            $user,
            $id,
            $input['payment_src_type'],
            $input['payment_src_id'],
            $input['payment_dst_type'],
            $input['payment_dst_id']
        );
        user_log(UserLog::ORDER_CLAIM, ['order_id' => $order->id], request());
        return new OrderResource($order);
    }

    public function revoke(Request $request, string $id)
    {
        $user = auth()->user();
        $this->checkSecurityCode($user, $request->input('security_code'));
        $order = $this->OrderService->revoke($user, $id);
        user_log(UserLog::ORDER_REVOKE, ['order_id' => $order->id], request());
        return new OrderResource($order);
    }

    public function sendConfirmVerification(string $id)
    {
        $user = auth()->user();

        $order = $this->OrderRepo
            ->findOrFail($id);
        $this->checkAuthorization($order, 'src_user');

        if ($order->status !== ORDER::STATUS_CLAIMED) {
            throw new UnavailableStatusError('Order is not claimed yet.');
        }

        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_ORDER_CONFIRMATION,
            'data' => $user->email,
        ], $order);
        $this->VerificationRepo->notify($verification, $user, new OrderConfirmation($verification));
        return (new VerificationResource($verification))
            ->response()
            ->setStatusCode(201);
    }

    public function confirm(ConfirmOrderRequest $request, string $id)
    {
        $user = auth()->user();
        $input = $request->validated();

        # Check security_code
        $this->checkSecurityCode($user, $input['security_code']);

        # check verification
        if (!$order_confirmation = $this->VerificationRepo
            ->find($input['verification_id'])
        ) {
            throw new BadRequestError('verification not found');
        }
        $this->VerificationRepo->verify(
            $order_confirmation,
            $input['verification_code'],
            $user->email,
            Verification::TYPE_ORDER_CONFIRMATION
        );
        try {
            $order = $this->OrderService->confirm($user, $id);
        } catch (\Throwable $e) {
            Log::error('Confirm order failed, '.$e);
            throw new UnknownError;
        }

        # send notification
        $order->dst_user->notify(new OrderCompletedNotification($order));
        FcmOrderCompletedNotification::dispatch($order->dst_user, $order)->onQueue(config('services.fcm.queue_name'));

        # add order count
        $this->UserRepo->updateOrderCount($order->src_user, true);
        $this->UserRepo->updateOrderCount($order->dst_user, true);

        user_log(UserLog::ORDER_CONFIRM, ['order_id' => $order->id], request());
        return new OrderResource($order);
    }

    public function cancel(Request $request, string $id)
    {
        $user = auth()->user();

        # Check security_code
        $this->checkSecurityCode($user, $request->input('security_code'));

        $order = $this->OrderService
            ->cancel($user, $id);

        # send notification
        $order->src_user->notify(new OrderCanceledNotification($order));
        $order->dst_user->notify(new OrderCanceledNotification($order));
        FcmOrderCanceledNotification::dispatch($order->src_user, $order)->onQueue(config('services.fcm.queue_name'));

        # add order count
        $this->UserRepo->updateOrderCount($order->dst_user, false);

        user_log(UserLog::ORDER_CANCEL, ['order_id' => $order->id], request());
        return response(null, 204);
    }

    protected function checkAuthorization(Order $order, $role = null)
    {
        $user = auth()->user();
        if ($role) {
            if (!$order->{$role}->is($user)) {
                throw new AccessDeniedHttpException;
            }
        } elseif (!$order->src_user->is($user) AND !$order->dst_user->is($user)) {
            throw new AccessDeniedHttpException;
        }
        return true;
    }
}
