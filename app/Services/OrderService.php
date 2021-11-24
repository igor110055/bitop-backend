<?php

namespace App\Services;

use DB;
use Dec\Dec;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
};
use App\Exceptions\{
    Core\BadRequestError,
    UnavailableStatusError,
    MinimumTradesError,
    OrderExpiredError,
    ExceedMinMaxLimitError,
};
use App\Models\{
    Advertisement,
    AssetTransaction,
    BankAccount,
    FeeSetting,
    Order,
    Transaction,
    User,
    AdminAction,
    SystemAction,
    Wfpayment,
};
use App\Notifications\{
    ClaimNotification,
    DealNotification,
    OrderCanceledNotification,
    OrderRevokedNotification,
    AdvertisementUnavailableNotification,
};
use App\Repos\Interfaces\{
    AccountRepo,
    AdvertisementRepo,
    BankAccountRepo,
    CoinExchangeRateRepo,
    OrderRepo,
    SystemActionRepo,
    UserRepo,
    WfpaymentRepo,
};
use App\Jobs\Fcm\{
    DealNotification as FcmDealNotification,
    ClaimNotification as FcmClaimNotification,
    OrderCanceledNotification as FcmOrderCanceledNotification,
    OrderRevokedNotification as FcmOrderRevokedNotification,
};

class OrderService implements OrderServiceInterface
{
    public function __construct(
        AccountRepo $AccountRepo,
        AdvertisementRepo $AdvertisementRepo,
        AssetServiceInterface $AssetService,
        FeeServiceInterface $FeeService,
        ExchangeServiceInterface $ExchangeService,
        OrderRepo $OrderRepo,
        AccountServiceInterface $AccountService,
        AdvertisementServiceInterface $AdvertisementService,
        BankAccountRepo $BankAccountRepo,
        SystemActionRepo $SystemActionRepo,
        UserRepo $UserRepo,
        WfpaymentRepo $WfpaymentRepo,
        WfpayServiceInterface $WfpayService
    ) {
        $this->AccountRepo = $AccountRepo;
        $this->AdvertisementRepo = $AdvertisementRepo;
        $this->AssetService = $AssetService;
        $this->ExchangeService = $ExchangeService;
        $this->BankAccountRepo = $BankAccountRepo;
        $this->OrderRepo = $OrderRepo;
        $this->SystemActionRepo = $SystemActionRepo;
        $this->UserRepo = $UserRepo;
        $this->WfpaymentRepo = $WfpaymentRepo;
        $this->AccountService = $AccountService;
        $this->FeeService = $FeeService;
        $this->AdvertisementService = $AdvertisementService;
        $this->WfpayService = $WfpayService;
    }

    public function make(
        User $user,
        Advertisement $advertisement,
        $amount,
        array $payables
    ) {
        if ($advertisement->status !== Advertisement::STATUS_AVAILABLE) {
            throw new UnavailableStatusError('Advertisemet is not available.');
        }

        if (Dec::lt($user->trade_number, $advertisement->min_trades)) {
            throw new MinimumTradesError;
        }

        # get request amount, total
        extract( # $amount, $total, $unit_price
            $this->ExchangeService->getTotalAndAmount(
                $advertisement->coin,
                $advertisement->currency,
                $advertisement->unit_price,
                $amount,
                null # total
            )
        );
        # check amount range
        if (Dec::gt($amount, $advertisement->remaining_amount)) {
            throw new BadRequestError('Requested amount exceeds the remaining amount');
        }
        # check price range
        if (Dec::lt($total, $advertisement->min_limit) or
            Dec::gt($total, $advertisement->max_limit)
        ) {
            throw new ExceedMinMaxLimitError;
        }

        list($order, $ad_deactivate) = DB::transaction(function () use ($user, $advertisement, $amount, $total, $payables) {
            $origin_ad = $advertisement;
            $advertisement = $this->AdvertisementRepo->findForUpdate($advertisement->id);
            // check advertisement value
            if (!$this->AdvertisementRepo->checkValuesUnchanged($origin_ad, $advertisement)) {
                throw new UnavailableStatusError;
            }

            $values = $advertisement->toArray();
            $values['amount'] = $amount;
            $values['total'] = $total;
            $values['advertisement_id'] = $advertisement->id;

            if ($advertisement->type === Advertisement::TYPE_SELL) {
                $src_user = $advertisement->owner;
                $dst_user = $user;
            } else {
                $src_user = $user;
                $dst_user = $advertisement->owner;
            }
            $values['src_user_id'] = $src_user->id;
            $values['dst_user_id'] = $dst_user->id;

            if ($src_user->is($dst_user)) {
                throw new BadRequestError('User can\'t buy/sell an order of his own.');
            }

            $update_ad = [];
            if (Dec::eq($amount, $advertisement->remaining_amount)) { #ad complete
                $update_ad['status'] = Advertisement::STATUS_COMPLETED;
            }
            $update_ad['remaining_amount'] = (string) Dec::sub($advertisement->remaining_amount, $amount);
            $remaining_price = Dec::mul($update_ad['remaining_amount'], $advertisement->unit_price, config('currency')[$advertisement->currency]['decimal']);
            if (Dec::lt($remaining_price, $advertisement->max_limit)) {
                $update_ad['max_limit'] = (string) $remaining_price;
            }

            if ($advertisement->type === Advertisement::TYPE_SELL) {
                # calculate sell advertisement remaining fee
                if (Dec::eq($amount, $advertisement->remaining_amount)) { #ad complete
                    $request_fee = $advertisement->remaining_fee;
                } else {
                    $request_fee = $this->AdvertisementRepo->calculateProportionFee($advertisement, $amount);
                    if (Dec::gt($request_fee, $advertisement->remaining_fee)) {
                        $request_fee = $advertisement->remaining_fee;
                    }
                }
                $update_ad['remaining_fee'] = (string) Dec::sub($advertisement->remaining_fee, $request_fee);

                # unlock locked-balance
                $locked_amount = (string)Dec::add($amount, $request_fee);
                $this->AccountService
                    ->unlock(
                        $advertisement->owner,
                        $advertisement->coin,
                        $locked_amount,
                        Transaction::TYPE_MATCH_ADVERTISEMENT,
                        $advertisement
                    );
                $values['fee'] = $request_fee;
            }
            # update advertisement
            $this->AdvertisementRepo->setAttribute($advertisement, $update_ad);
            $advertisement->refresh();
            if (($ad_deactivate = $advertisement->remaining_below_limit) and
                !Dec::eq($advertisement->remaining_amount, 0)
            ) {
                $this->AdvertisementService->deactivate($advertisement->owner, $advertisement);
            }

            # Calculate src_user's fee if this is a buy-advertisement
            if ($advertisement->type === Advertisement::TYPE_BUY) {
                $fee = $this->FeeService
                    ->getFee(
                        FeeSetting::TYPE_ORDER,
                        $src_user,
                        $advertisement->coin,
                        $amount
                    );
                $values['fee'] = data_get($fee, 'amount');
                $values['fee_setting_id'] = data_get($fee, 'fee_setting.id');
            }

            # Generate expired_at from advertisement's payment_window
            $values['expired_at'] = Carbon::now()->addMinutes($advertisement->payment_window);

            # Create the order
            $order = $this->OrderRepo->create($values);

            # Attach bank_accounts to order if this is a buy advertisement
            if ($advertisement->type === Advertisement::TYPE_BUY) {
                $bank_account_ids = data_get($payables, Order::PAYABLE_BANK_ACCOUNT, []);
                $filtered_bank_accounts = $this->BankAccountRepo
                    ->filterWithIds($bank_account_ids, [
                        'currency' => $advertisement->currency,
                        'user_id' => $user->id,
                    ]);

                if (empty($filtered_bank_accounts)) {
                    throw new BadRequestError('No valid bank_account provided.');
                }
                $order->bank_accounts()->attach($filtered_bank_accounts->pluck('id'));
            }

            # Copy bank_accounts from advertisement to order if this is a sell advertisement
            if ($advertisement->type === Advertisement::TYPE_SELL) {
                $bank_accounts = $advertisement->bank_accounts;
                $order->bank_accounts()->attach($bank_accounts->pluck('id')->toArray());
            }

            # Lock balance of src_user
            $locked_amount = (string)Dec::add($order->amount, $order->fee);
            $this->AccountService
                ->lock(
                    $src_user,
                    $order->coin,
                    $locked_amount,
                    Transaction::TYPE_CREATE_ORDER,
                    $order
                );

            return [$order, $ad_deactivate];
        });

        # send notificaiton
        if ($ad_deactivate) {
            $advertisement->owner->notify((new AdvertisementUnavailableNotification(
                $advertisement,
                SystemAction::class
            ))->delay(Carbon::now()->addMinute()));
        }
        $action = ($advertisement->type === Advertisement::TYPE_SELL) ? 'buy' : 'sell';
        $dst_user_notification = new DealNotification($order, 'dst_user', $action);
        $order->dst_user->notify($dst_user_notification);
        $src_user_notification = new DealNotification($order, 'src_user', $action);
        $order->src_user->notify($src_user_notification);
        /* FcmDealNotification::dispatch($order->dst_user, $order)->onQueue(config('services.fcm.queue_name'));
        FcmDealNotification::dispatch($order->src_user, $order)->onQueue(config('services.fcm.queue_name')); */

        return $order;
    }

    public function makeExpress(
        User $user,
        Advertisement $advertisement,
        $amount = null,
        $total = null,
        $payment_method = null,
        $payables = null
    ) {
        if (!$advertisement->is_express) {
            throw new BadRequestError;
        }
        if ($advertisement->status !== Advertisement::STATUS_AVAILABLE) {
            throw new UnavailableStatusError('Advertisemet is not available.');
        }
        if (Dec::lt($user->trade_number, $advertisement->min_trades)) {
            throw new MinimumTradesError;
        }

        extract(    // $amount, $total, $unit_price
            $this->ExchangeService->getTotalAndAmount(
                $advertisement->coin,
                $advertisement->currency,
                $advertisement->unit_price,
                $amount,
                $total
            )
        );

        # check amount range
        if (Dec::gt($amount, $advertisement->remaining_amount)) {
            throw new BadRequestError('Requested amount exceeds the remaining amount');
        }
        # check total range
        if (Dec::lt($total, $advertisement->min_limit) or
            Dec::gt($total, $advertisement->max_limit)
        ) {
            throw new ExceedMinMaxLimitError;
        }

        if ($advertisement->type === Advertisement::TYPE_SELL) {
            # check total range for payment method
            if (Dec::lt($total, Wfpayment::$limits[$payment_method]['min']) or
                Dec::gt($total, Wfpayment::$limits[$payment_method]['max'])
            ) {
                throw new ExceedMinMaxLimitError;
            }
        }

        list($order, $ad_deactivate, $wfpayment) = DB::transaction(function () use ($user, $advertisement, $amount, $total, $payment_method, $payables) {
            $origin_ad = $advertisement;
            $advertisement = $this->AdvertisementRepo->findForUpdate($advertisement->id);
            // check advertisement value
            if (!$this->AdvertisementRepo->checkValuesUnchanged($origin_ad, $advertisement)) {
                throw new UnavailableStatusError;
            }

            $values['coin'] = $advertisement->coin;
            $values['currency'] = $advertisement->currency;
            $values['unit_price'] = $advertisement->unit_price;
            $values['advertisement_id'] = $advertisement->id;
            $values['is_express'] = true;
            $values['amount'] = $amount;
            $values['total'] = $total;

            if ($advertisement->type === Advertisement::TYPE_SELL) {
                $src_user = $advertisement->owner;
                $dst_user = $user;
            } else {
                $src_user = $user;
                $dst_user = $advertisement->owner;
            }
            $values['src_user_id'] = $src_user->id;
            $values['dst_user_id'] = $dst_user->id;

            if ($src_user->is($dst_user)) {
                throw new BadRequestError('User can\'t buy/sell an order of his own.');
            }

            $update_ad = [];
            if (Dec::eq($amount, $advertisement->remaining_amount)) { #ad complete
                $update_ad['status'] = Advertisement::STATUS_COMPLETED;
            }
            $update_ad['remaining_amount'] = (string) Dec::sub($advertisement->remaining_amount, $amount);
            $remaining_total = Dec::mul($update_ad['remaining_amount'], $advertisement->unit_price, config('currency')[$advertisement->currency]['decimal']);
            if (Dec::lt($remaining_total, $advertisement->max_limit)) {
                $update_ad['max_limit'] = (string) $remaining_total;
            }

            if ($advertisement->type === Advertisement::TYPE_SELL) {
                # calculate sell advertisement remaining fee
                if (Dec::eq($amount, $advertisement->remaining_amount)) { #ad complete
                    $request_fee = $advertisement->remaining_fee;
                } else {
                    $request_fee = $this->AdvertisementRepo->calculateProportionFee($advertisement, $amount);
                    if (Dec::gt($request_fee, $advertisement->remaining_fee)) {
                        $request_fee = $advertisement->remaining_fee;
                    }
                }
                $update_ad['remaining_fee'] = (string) Dec::sub($advertisement->remaining_fee, $request_fee);

                # unlock locked-balance
                $locked_amount = (string)Dec::add($amount, $request_fee);
                $this->AccountService
                    ->unlock(
                        $advertisement->owner,
                        $advertisement->coin,
                        $locked_amount,
                        Transaction::TYPE_MATCH_ADVERTISEMENT,
                        $advertisement
                    );
                $values['fee'] = $request_fee;
            }
            # update advertisement
            $this->AdvertisementRepo->setAttribute($advertisement, $update_ad);
            $advertisement->refresh();
            if (($ad_deactivate = $advertisement->remaining_below_limit) and
                !Dec::eq($advertisement->remaining_amount, 0)
            ) {
                $this->AdvertisementService->deactivate($advertisement->owner, $advertisement);
            }

            # Calculate src_user's fee if this is a buy-advertisement
            if ($advertisement->type === Advertisement::TYPE_BUY) {
                $fee = $this->FeeService
                    ->getFee(
                        FeeSetting::TYPE_ORDER,
                        $src_user,
                        $advertisement->coin,
                        $amount
                    );
                $values['fee'] = data_get($fee, 'amount');
                $values['fee_setting_id'] = data_get($fee, 'fee_setting.id');
            }

            # Generate expired_at from advertisement's payment_window
            $values['expired_at'] = Carbon::now()->addMinutes($advertisement->payment_window);

            # Create the order
            $order = $this->OrderRepo->create($values);

            # Attach bank_accounts to order if this is a buy advertisement
            if ($advertisement->type === Advertisement::TYPE_BUY) {
                $bank_account_ids = data_get($payables, Order::PAYABLE_BANK_ACCOUNT, []);
                $filtered_bank_accounts = $this->BankAccountRepo
                    ->filterWithIds($bank_account_ids, [
                        'currency' => $advertisement->currency,
                        'user_id' => $user->id,
                    ]);

                if (empty($filtered_bank_accounts)) {
                    throw new BadRequestError('No valid bank_account provided.');
                }
                $bank_account = $filtered_bank_accounts->first();
                $order->bank_accounts()->attach($bank_account->id);
            }

            # Lock balance of src_user
            $locked_amount = (string)Dec::add($order->amount, $order->fee);
            $this->AccountService
                ->lock(
                    $src_user,
                    $order->coin,
                    $locked_amount,
                    Transaction::TYPE_CREATE_ORDER,
                    $order
                );

            # Create wfpayment and get remote payment info
            if ($advertisement->type === Advertisement::TYPE_SELL) {
                $wfpayment = $this->WfpaymentRepo
                    ->createByOrder($order, $payment_method);
            } else {
                $wfpayment = null;
            }

            return [$order, $ad_deactivate, $wfpayment];
        });

        # send notificaiton
        if ($ad_deactivate) {
            $advertisement->owner->notify((new AdvertisementUnavailableNotification(
                $advertisement,
                SystemAction::class
            ))->delay(Carbon::now()->addMinute()));
        }
        $action = ($advertisement->type === Advertisement::TYPE_SELL) ? 'buy' : 'sell';
        $dst_user_notification = new DealNotification($order, 'dst_user', $action);
        $order->dst_user->notify($dst_user_notification);
        $src_user_notification = new DealNotification($order, 'src_user', $action);
        $order->src_user->notify($src_user_notification);
        /* FcmDealNotification::dispatch($order->dst_user, $order)->onQueue(config('services.fcm.queue_name'));
        FcmDealNotification::dispatch($order->src_user, $order)->onQueue(config('services.fcm.queue_name')); */

        return [$order, $wfpayment];
    }

    public function claim(
        User $user,
        $order_id,
        $payment_src_type,
        $payment_src_id,
        $payment_dst_type,
        $payment_dst_id
    ) {
        $order = DB::transaction(function () use ($user, $order_id, $payment_src_type, $payment_src_id, $payment_dst_type, $payment_dst_id) {

            $order = $this->OrderRepo
                ->findForUpdate($order_id);

            if (!$user->is($order->dst_user)) {
                throw new AccessDeniedHttpException;
            }

            if ($order->is_expired) {
                throw new OrderExpiredError;
            }

            if ($order->status !== Order::STATUS_PROCESSING) {
                throw new UnavailableStatusError('Order status is wrong.');
            }

            if ($payment_dst_type === Order::PAYABLE_BANK_ACCOUNT) {
                $dst_bank_account = $this->BankAccountRepo
                    ->findOrFail($payment_dst_id);
                if (!$order->bank_accounts->contains($dst_bank_account)) {
                    throw new BadRequestError('bank_account provided is not in the list');
                }
                $order->payment_dst()->associate($dst_bank_account);
                $order->save();
            } else {
                throw new BadRequestError('Only bank_account is supported.');
            }

            if ($payment_src_type === Order::PAYABLE_BANK_ACCOUNT) {
                $src_bank_account = $this->BankAccountRepo
                    ->findOrFail($payment_src_id);
                if (!$src_bank_account->owner->is($user)) {
                    throw new BadRequestError('payment_src provided is not of the user');
                }
                if (!in_array($order->currency, $src_bank_account->currency)) {
                    throw new BadRequestError('currency of payment_src doesnt contain order currency');
                }
                $order->payment_src()->associate($src_bank_account);
                $order->save();
            } else {
                throw new BadRequestError('Only bank_account is supported.');
            }

            $this->OrderRepo
                ->update($order, [
                    'status' => Order::STATUS_CLAIMED,
                    'claimed_at' => Carbon::now(),
                ]);

            return $order->fresh();
        });

        # send notification
        $order->src_user->notify(new ClaimNotification($order));
        FcmClaimNotification::dispatch($order->src_user, $order)->onQueue(config('services.fcm.queue_name'));

        return $order;
    }

    public function confirm(
        User $user,
        $order_id
    ) {
        return DB::transaction(function () use ($user, $order_id) {

            $order = $this->OrderRepo
                ->findForUpdate($order_id);

            if (!$user->is($order->src_user)) {
                throw new AccessDeniedHttpException;
            }

            if ($order->status !== Order::STATUS_CLAIMED) {
                throw new UnavailableStatusError('Order status is wrong.');
            }
            /* Agent feature is not activated now
            # Get Order Porfit and unit prices
            $profit_data = $this->getProfitUnitPrice($order); */

            # Update Order
            $this->OrderRepo
                ->update($order, [
                    'profit' => '0',
                    # 'profit' => $profit_data['profit'],
                    # 'currency_unit_price' => $profit_data['currency_unit_price'],
                    # 'coin_unit_price' => $profit_data['coin_unit_price'],
                    'status' => Order::STATUS_COMPLETED,
                    'completed_at' => Carbon::now(),
                ]);

            # unlock locked-balance of src_user
            $locked_amount = (string)Dec::add($order->amount, $order->fee);
            $this->AccountService
                ->unlock(
                    $order->src_user,
                    $order->coin,
                    $locked_amount,
                    Transaction::TYPE_COMPLETE_ORDER,
                    $order
                );

            # withdraw from src_user
            $this->AccountService
                ->withdraw(
                    $order->src_user,
                    $order->coin,
                    $order->amount,
                    Transaction::TYPE_SELL_ORDER,
                    $order
                );

            # withdraw fee from src_user
            if (Dec::create($order->fee)->isPositive()) {
                $this->AccountService
                    ->withdraw(
                        $order->src_user,
                        $order->coin,
                        $order->fee,
                        Transaction::TYPE_ORDER_FEE,
                        $order
                    );

                # share the fee
                $fee_shares = $this->FeeService
                    ->getFeeShares(
                        $order->coin,
                        $order->fee,
                        $order->src_user->group
                    );
                foreach ($fee_shares as $share) {
                    $this->AccountService
                        ->deposit(
                            $share['user'],
                            $order->coin,
                            $share['amount'],
                            Transaction::TYPE_FEE_SHARE,
                            null,                           # unit_price
                            $order
                        );
                }
            }

            # deposit to dst_user
            $this->AccountService
                ->deposit(
                    $order->dst_user,
                    $order->coin,
                    $order->amount,
                    Transaction::TYPE_BUY_ORDER,
                    null,                                   # $profit_data['new_coin_unit_price'],                # unit_price
                    $order
                );

            /* This agent feature is not activated now
            # process asset if src_user is an agent
            if ($order->src_user->is_agent) {
                # deposit to agency's asset
                $agency = $order->src_user->agency;
                $this->AssetService
                    ->deposit(
                        $agency,
                        $order->currency,
                        $order->price,
                        AssetTransaction::TYPE_SELL_ORDER,
                        null,                               # unit_price: asset's unit_pirce will not be changed by order
                        $order
                    );
            }

            # process asset if dst_user is an agent
            if ($order->dst_user->is_agent) {
                # withdraw from agency's asset
                $agency = $order->dst_user->agency;
                if (Dec::create($order->price)->isPositive()) {
                    $this->AssetService
                        ->withdraw(
                            $agency,
                            $order->currency,
                            $order->price,
                            AssetTransaction::TYPE_BUY_ORDER,
                            $order
                        );
                }
            } */

            return $order->fresh();
        });
    }

    public function getProfitUnitPrice(Order $order)
    {
        return $this->calculateProfitUnitPrice(
            $order->dst_user,
            $order->src_user,
            $order->coin,
            $order->amount,
            $order->unit_price,
            $order->currency,
            $order->price
        );
    }

    public function calculateProfitUnitPrice(
        $dst_user,
        $src_user,
        $coin,
        $coin_amount,
        $coin_unit_price,
        $currency,
        $currency_amount
    ) {
        $scale = config('core.currency.scale');
        $rate_scale = config('core.currency.rate_scale');

        # Get coin unit price frim src_user's account
        $coin_unit_price = $this->AccountRepo
            ->findByUserCoinOrFail($src_user, $coin)
            ->unit_price;

        # Get currency price
        $currency_unit_price_source = $dst_user->is_agent ? $dst_user : $src_user;
        $currency_unit_price = $this->ExchangeService
                ->getAgencyCurrencyPrice($currency_unit_price_source, $currency);

        # Calculate profit
        if (($coin === config('core.coin.control')) and
            !$src_user->is_agent and
            $dst_user->is_agent and
            !is_null($currency_unit_price) and
            !is_null($coin_unit_price)
        ) {
            $revenue = Dec::mul($coin_amount, $coin_unit_price);
            $cost = Dec::mul($currency_amount, $currency_unit_price);
            $profit = (string) Dec::sub($revenue, $cost, $scale);
        } else {
            $profit = "0";
        }

        if (!is_null($currency_unit_price)) {
            $new_coin_unit_price = (string)Dec::mul($currency_amount, $currency_unit_price)->div($coin_amount, $rate_scale);
        } else {
            $new_coin_unit_price = null;
        }

        return [
            'profit' => $profit,
            'coin_unit_price' => $coin_unit_price,
            'currency_unit_price' => $currency_unit_price,
            'new_coin_unit_price' => $new_coin_unit_price,
        ];
    }

    public function cancel(
        User $user,
        $order_id,
        $action = User::class
    ) {
        return DB::transaction(function () use ($user, $order_id, $action) {

            $order = $this->OrderRepo
                ->findForUpdate($order_id);

            if (!$user->is($order->dst_user)) {
                throw new AccessDeniedHttpException;
            }

            if (($action === AdminAction::class) and ($order->status !== Order::STATUS_CLAIMED)) {
                throw new UnavailableStatusError('Wrong order status');
            }

            if (($action === SystemAction::class) and ($order->status !== Order::STATUS_PROCESSING)) {
                throw new UnavailableStatusError('Wrong order status');
            }

            if (($action === User::class) and ($order->status !== Order::STATUS_PROCESSING)) {
                throw new UnavailableStatusError('Wrong order status');
            }

            $this->OrderRepo
                ->update($order, [
                    'status' => Order::STATUS_CANCELED,
                    'canceled_at' => Carbon::now(),
                ]);

            # unlock locked-balance of src_user
            $locked_amount = (string)Dec::add($order->amount, $order->fee);
            $this->AccountService
                ->unlock(
                    $order->src_user,
                    $order->coin,
                    $locked_amount,
                    Transaction::TYPE_CANCEL_ORDER,
                    $order
                );

            return $order->fresh();
        });
    }

    public function revoke(
        User $user,
        $order_id
    ) {
        list($order, $canceled) = DB::transaction(function () use ($user, $order_id) {
            $order = $this->OrderRepo
                ->findForUpdate($order_id);

            if (!$user->is($order->dst_user)) {
                throw new AccessDeniedHttpException;
            }

            if ($order->revoked_at) {
                throw new BadRequestError('Exceed revoke limit');
            }

            if ($order->status !== Order::STATUS_CLAIMED) {
                throw new UnavailableStatusError('Order status is wrong.');
            }

            $order->payment_dst()->dissociate();
            $order->payment_src()->dissociate();
            $order->save();

            $this->OrderRepo
                ->update($order, [
                    'status' => Order::STATUS_PROCESSING,
                    'claimed_at' => null,
                    'revoked_at' => Carbon::now(),
                ]);

            # system auto cancel after revoke due to order expiration
            if ($order->is_expired) {
                $this->cancel($user, $order->id, SystemAction::class);
                $this->SystemActionRepo->createByApplicable($order, [
                    'type' => SystemAction::TYPE_CANCEL_ORDER,
                    'description' => 'System cancel this order due to expiration after user revoke',
                ]);
                return [$order->fresh(), true];
            }
            return [$order->fresh(), false];
        });

        # revoke notification
        $order->src_user->notify(new OrderRevokedNotification($order));
        FcmOrderRevokedNotification::dispatch($order->src_user, $order)->onQueue(config('services.fcm.queue_name'));

        if ($canceled) {
            # record user order count
            $this->UserRepo->updateOrderCount($user, false);

            # system canceled notification
            $order->src_user->notify(new OrderCanceledNotification($order, SystemAction::class));
            $order->dst_user->notify(new OrderCanceledNotification($order, SystemAction::class));
            FcmOrderCanceledNotification::dispatch($order->src_user, $order, SystemAction::class)->onQueue(config('services.fcm.queue_name'));
            FcmOrderCanceledNotification::dispatch($order->dst_user, $order, SystemAction::class)->onQueue(config('services.fcm.queue_name'));
        }
        return $order;
    }

    public function getWfpayment(
        Order $order,
        $payment_method = null,
        $force_create = false
    ) {
        $order = $this->OrderRepo
            ->findOrFail($order_id);

        assert($order->is_express, true);

        # TODO: check status and existing payment

        if ($force_create) {
            $wfpayment = null;
        } else {
            $wfpayment = $this->WfpaymentRepo
                ->getTheLatestByOrder($order);
        }

        if (is_null($wfpayment)) {
            $wfpayment = $this->WfpaymentRepo
                ->createByOrder($order, $payment_method);
        }

        return $wfpayment;
    }
}
