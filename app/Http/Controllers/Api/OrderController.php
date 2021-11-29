<?php

namespace App\Http\Controllers\Api;

use Dec\Dec;
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
use Illuminate\Database\{
    Eloquent\ModelNotFoundException,
};
use App\Http\Resources\{
    AdvertisementResource,
    OrderResource,
    VerificationResource,
};
use App\Http\Requests\{
    OrderListRequest,
    ClaimOrderRequest,
    ConfirmOrderRequest,
    ExpressSettingsRequest,
    MatchExpressAdsRequest,
    PreviewTradeRequest,
    TradeRequest,
    ExpressTradeRequest,
};
use App\Models\{
    Order,
    Verification,
    UserLog,
    Advertisement,
    FeeSetting,
    Wfpayment,
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
    WfpaymentRepo,
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
        WfpaymentRepo $WfpaymentRepo,
        OrderServiceInterface $OrderService,
        FeeServiceInterface $FeeService,
        ExchangeServiceInterface $ExchangeService
    ) {
        parent::__construct();
        $this->UserRepo = $UserRepo;
        $this->OrderRepo = $OrderRepo;
        $this->AdvertisementRepo = $AdvertisementRepo;
        $this->VerificationRepo = $VerificationRepo;
        $this->WfpaymentRepo = $WfpaymentRepo;
        $this->OrderService = $OrderService;
        $this->FeeService = $FeeService;
        $this->ExchangeService = $ExchangeService;
        $this->coins = config('coin');

        $this->middleware(
            'real_name.check',
            ['only' => ['previewTrade', 'trade', 'matchExpressTrade', 'getExpressTradeSettings']]
        );
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
        if ($order->is_express) {
            $wfpayment = $this->WfpaymentRepo
                ->getTheLatestByOrder($order);
            return (new OrderResource($order))->withEvent($order->current_timeline)->withPayment($wfpayment);
        }
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

        $normalized = $this->ExchangeService->getTotalAndAmount(
            $advertisement->coin,
            $advertisement->currency,
            $advertisement->unit_price,
            $input['amount'],
            null # total
        );

        $result = [
            'coin' => $advertisement->coin,
            'currency' => $advertisement->currency,
            'amount' => $normalized['amount'],
            'unit_price' => $normalized['unit_price'],
            'total' => $normalized['total'],
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
                    $normalized['total']
                );
            $result['profit'] = $profit['profit'];
        } */
        return $result;
    }

    public function getExpressTradeSettings(ExpressSettingsRequest $request)
    {
        $values = $request->validated();
        $user = auth()->user();
        $payment_limit = Wfpayment::$limits;
        $paymeny_methods = Wfpayment::$methods;

        $coin = $values['coin'];
        $currency = $values['currency'];
        $action = $values['action'];
        $type = ($action === Advertisement::TYPE_BUY) ? Advertisement::TYPE_SELL : Advertisement::TYPE_BUY;

        $response = [];

        $ad = $this->AdvertisementRepo
            ->getExpressAds(
                $user,
                $type,
                $coin,
                $currency,
            )->first();
        if (is_null($ad)) {
            $price_result = $this->ExchangeService
                ->coinToCurrency(
                    $user,
                    $coin,
                    $currency,
                    $action
                );
            $response['unit_price'] = $price_result['unit_price'];
        } else {
            $response['unit_price'] = $ad->unit_price;
        }
        if ($action === Advertisement::TYPE_SELL) {
            $mins = [];
            $maxs = [];
            foreach ($paymeny_methods as $method) {
                $mins[] = $payment_limit[$method]['min'];
                $maxs[] = $payment_limit[$method]['max'];
            }
            $response['total_limit']['min'] = min($mins);
            $response['total_limit']['max'] = max($maxs);
        } else {
            if (is_null($ad)) {
                $response['total_limit']['min'] = $payment_limit[Wfpayment::METHOD_BANK]['min'];
                $response['total_limit']['max'] = $payment_limit[Wfpayment::METHOD_BANK]['max'];
            } else {
                $response['total_limit']['min'] = $ad->min_limit;
                $response['total_limit']['max'] = $ad->max_limit;
            }
        }

        if ($action === Advertisement::TYPE_SELL) {
            $fee = $this->FeeService->getFee(
                FeeSetting::TYPE_ORDER,
                $user, #src_user
                $coin,
                1
            );
            if (data_get($fee, 'fee_setting')) {
                if (data_get($fee, 'fee_setting.unit') !== '%') {
                    throw new BadRequestError('Fee setting error');
                }
                $response['fee_percentage'] = trim_zeros(data_get($fee, 'fee_setting.value', '0'));
            }
        }
        $response['fee_percentage'] = isset($response['fee_percentage']) ? $response['fee_percentage'] : '0';

        return $response;
    }

    public function matchExpressAds(MatchExpressAdsRequest $request)
    {
        $values = $request->validated();
        $user = auth()->user();
        $payment_limit = Wfpayment::$limits;
        $paymeny_methods = Wfpayment::$methods;

        $amount = data_get($values, 'amount');
        $total = data_get($values, 'total');
        $coin = $values['coin'];
        $currency = $values['currency'];
        $action = $values['action'];

        if (!is_null($amount)) {
            $amount = trim_redundant_decimal($amount, $coin);
        } else {
            $total = currency_trim_redundant_decimal($total, $currency);
        }
        $type = ($action === Advertisement::TYPE_BUY) ? Advertisement::TYPE_SELL : Advertisement::TYPE_BUY;

        $ads = $this->AdvertisementRepo
            ->getExpressAds(
                $user,
                $type,
                $coin,
                $currency,
            );

        foreach ($ads as $ad) {
            if (Dec::lt($user->trade_number, $ad->min_trades)) {
                continue;
            }

            $normalized = $this->ExchangeService->getTotalAndAmount(
                $coin,
                $currency,
                $ad['unit_price'],
                $amount,
                $total
            );
            extract($normalized); // $total, $amount, $unit_price

            if (Dec::gt($amount, $ad->remaining_amount)) {
                continue;
            }
            if (Dec::gt($total, $ad->max_limit)) {
                continue;
            }
            if (Dec::lt($total, $ad->min_limit)) {
                continue;
            }

            $result = [
                'advertisement_id' => $ad->id,
                'action' => $values['action'],
                'coin' => $coin,
                'currency' => $currency,
                'amount' => $amount,
                'total' => $total,
                'unit_price' => $unit_price,
            ];

            if ($action === Advertisement::TYPE_SELL) {
                $fee = $this->FeeService->getFee(
                    FeeSetting::TYPE_ORDER,
                    $user, #src_user
                    $coin,
                    $amount
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

            if ($action === Advertisement::TYPE_SELL) {
                return $result;
            }

            foreach ($paymeny_methods as $method) {
                $result_payment_method = [
                    'advertisement_id' => $ad->id,
                    'amount' => $amount,
                    'total' => $total,
                    'unit_price' => $unit_price,
                    'payment_method' => $method,
                    'total_limit' => $payment_limit[$method],
                    'is_available' => true,
                ];
                if (Dec::lt($result['total'], $payment_limit[$method]['min']) or Dec::gt($result['total'], $payment_limit[$method]['max'])) {
                    $result_payment_method['is_available'] = false;
                    $result_payment_method['error'] = 'OutOfRange';
                }
                $result['payment_methods'][] = $result_payment_method;
            }
            return $result;
        }
        throw new ModelNotFoundException;
    }

    public function trade(TradeRequest $request)
    {
        $user = auth()->user();
        $input = $request->validated();
        $this->checkSecurityCode($user, $input['security_code']);
        $advertisement = $this->AdvertisementRepo->findOrFail($input['advertisement_id']);
        if ($advertisement->is_express) {
            throw new ModelNotFoundException;
        }
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

    public function tradeExpress(ExpressTradeRequest $request)
    {
        $user = auth()->user();
        $values = $request->validated();
        $this->checkSecurityCode($user, $values['security_code']);
        $advertisement = $this->AdvertisementRepo->findOrFail($values['advertisement_id']);
        if (!$advertisement->is_express) {
            throw new BadRequestError;
        }

        $amount = data_get($values, 'amount');
        $total = data_get($values, 'total');
        $action = $values['action'];
        $payment_method = data_get($values, 'payment_method');
        if (!is_null($amount)) {
            $amount = trim_redundant_decimal($amount, $advertisement['coin']);
        } else {
            $total = currency_trim_redundant_decimal($total, $advertisement['currency']);
        }
        $type = ($action === Advertisement::TYPE_BUY) ? Advertisement::TYPE_SELL : Advertisement::TYPE_BUY;
        if ($advertisement->type !== $type) {
            throw new BadRequestError;
        }

        list($order, $wfpayment) = $this->OrderService
            ->makeExpress(
                $user,
                $advertisement,
                $amount,
                $total,
                $payment_method,
                data_get($values, 'payables', [])
            );

        user_log(UserLog::ORDER_CREATE, ['order_id' => $order->id], request());

        return response()->json(
            (new OrderResource($order))->withPayment($wfpayment),
            201
        );
    }

    public function claim(ClaimOrderRequest $request, string $id)
    {
        $user = auth()->user();
        $input = $request->validated();

        $order = $this->OrderRepo->findOrFail($id);
        if ($order->is_express) {
            throw new BadRequestError;
        }

        if (!$user->is($order->dst_user)) {
            throw new AccessDeniedHttpException;
        }

        $order = $this->OrderService->claim(
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

        $order = $this->OrderRepo->findOrFail($id);
        if ($order->is_express) {
            throw new BadRequestError;
        }

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

        if ($order->is_express) {
            throw new BadRequestError;
        }

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

        $order = $this->OrderRepo->findOrFail($id);
        if (!$user->is($order->src_user)) {
            throw new AccessDeniedHttpException;
        }
        if ($order->status !== Order::STATUS_CLAIMED) {
            throw new UnavailableStatusError('Order status is wrong.');
        }
        if ($order->is_express) {
            throw new BadRequestError;
        }

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
            $order = $this->OrderService->confirm($id);
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
