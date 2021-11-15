<?php

namespace App\Services;

use DB;
use Dec\Dec;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\{
    ConflictHttpException,
    NotFoundHttpException,
};
use Illuminate\Database\{
    QueryException,
    Eloquent\ModelNotFoundException,
};
use Illuminate\Support\Facades\Log;
use App\Notifications\WithdrawalBadRequestNotification;
use App\Models\{
    FeeSetting,
    Limitation,
    Transaction,
    User,
    Withdrawal,
    Account,
    WalletBalanceLog,
    WalletManipulation,
    WalletLog,
    Config,
    SystemAction,
};
use App\Repos\Interfaces\{
    AccountRepo,
    DepositRepo,
    LimitationRepo,
    ManipulationRepo,
    TransactionRepo,
    WithdrawalRepo,
    FeeSettingRepo,
    WalletBalanceRepo,
    WalletBalanceLogRepo,
    WalletManipulationRepo,
    WalletLogRepo,
    ConfigRepo,
    SystemActionRepo,
};
use App\Exceptions\{
    Account\InsufficientBalanceError,
    Core\BadRequestError,
    DuplicateRecordError,
    ServiceUnavailableError,
    VendorException,
    WithdrawalStatusError,
    WithdrawLimitationError,
};
use App\Jobs\Fcm\WithdrawalBadRequestNotification as FcmWithdrawalBadRequestNotification;

class AccountService implements  AccountServiceInterface
{
    public function __construct(
        AccountRepo $AccountRepo,
        DepositRepo $DepositRepo,
        LimitationRepo $LimitationRepo,
        ManipulationRepo $ManipulationRepo,
        TransactionRepo $TransactionRepo,
        WithdrawalRepo $WithdrawalRepo,
        FeeSettingRepo $FeeSettingRepo,
        WalletBalanceRepo $WalletBalanceRepo,
        WalletBalanceLogRepo $WalletBalanceLogRepo,
        WalletManipulationRepo $WalletManipulationRepo,
        WalletLogRepo $WalletLogRepo,
        ConfigRepo $ConfigRepo,
        SystemActionRepo $SystemActionRepo,
        FeeServiceInterface $FeeService,
        WalletServiceInterface $WalletService,
        ExchangeServiceInterface $ExchangeService
    ) {
        $this->AccountRepo = $AccountRepo;
        $this->DepositRepo = $DepositRepo;
        $this->LimitationRepo = $LimitationRepo;
        $this->ManipulationRepo = $ManipulationRepo;
        $this->TransactionRepo = $TransactionRepo;
        $this->WithdrawalRepo = $WithdrawalRepo;
        $this->FeeSettingRepo = $FeeSettingRepo;
        $this->WalletBalanceRepo = $WalletBalanceRepo;
        $this->WalletBalanceLogRepo = $WalletBalanceLogRepo;
        $this->WalletManipulationRepo = $WalletManipulationRepo;
        $this->WalletLogRepo = $WalletLogRepo;
        $this->ConfigRepo = $ConfigRepo;
        $this->SystemActionRepo = $SystemActionRepo;
        $this->FeeService = $FeeService;
        $this->WalletService = $WalletService;
        $this->ExchangeService = $ExchangeService;
        $this->coins = config('coin');
        $this->wallet_coin_map = config('services.wallet.coin_map');
        $this->wallet_reverse_coin_map = config('services.wallet.reverse_coin_map');
    }

    public function lock(
        $user,
        string $coin,
        string $amount,
        string $type,
        $transactable = null
    ) {
        assert(in_array($coin, array_keys($this->coins)));

        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);

        return DB::transaction(function () use ($user, $account, $amount, $type, $transactable) {
            $account = $this->AccountRepo
                ->lockByAccount(
                    $account,
                    $amount
                );
            $transaction = $this->TransactionRepo
                ->create(
                    $account,
                    $account->coin,
                    $type,
                    $amount,
                    $account->locked_balance,
                    null,                       # unit_price
                    $account->unit_price,       # result_unit_price
                    true,                       # is_locked
                    $transactable,
                    true,                       # status
                    null                        # message
                );
            return $account;
        });
    }

    public function unlock(
        $user,
        string $coin,
        string $amount,
        string $type,
        $transactable = null
    ) {
        assert(in_array($coin, array_keys($this->coins)));

        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);

        return DB::transaction(function () use ($user, $account, $amount, $type, $transactable) {
            $account = $this->AccountRepo
                ->unlockByAccount(
                    $account,
                    $amount
                );
            $transaction = $this->TransactionRepo
                ->create(
                    $account,
                    $account->coin,
                    $type,
                    (string)Dec::create($amount)->additiveInverse(),
                    $account->locked_balance,
                    null,                       # unit_price
                    $account->unit_price,       # result_unit_price
                    true,                       # is_locked
                    $transactable,
                    true,                       # status
                    null                        # message
                );
            return $account;
        });
    }

    public function deposit(
        $user,
        string $coin,
        string $amount,
        string $type,
        $unit_price = null,
        $transactable = null,
        string $message = null
    ) {
        assert(in_array($coin, array_keys($this->coins)));

        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);

        return DB::transaction(function () use ($user, $account, $amount, $type, $unit_price, $transactable, $message) {
            $account = $this->AccountRepo
                ->depositByAccount(
                    $account,
                    $amount,
                    $unit_price
                );
            $transaction = $this->TransactionRepo
                ->create(
                    $account,
                    $account->coin,
                    $type,
                    $amount,
                    $account->balance,
                    $unit_price,            # unit_price
                    $account->unit_price,   # result_unit_price
                    false,                  # is_locked
                    $transactable,
                    true,                   # status
                    $message
                );
            return $account;
        });
    }

    public function withdraw(
        $user,
        string $coin,
        string $amount,
        string $type,
        $transactable = null,
        string $message = null
    ) {
        assert(in_array($coin, array_keys($this->coins)));

        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);

        return DB::transaction(function () use ($user, $account, $amount, $type, $transactable, $message) {
            $account = $this->AccountRepo
                ->withdrawByAccount(
                    $account,
                    $amount
                );
            $transaction = $this->TransactionRepo
                ->create(
                    $account,
                    $account->coin,
                    $type,
                    (string)Dec::create($amount)->additiveInverse(),
                    $account->balance,
                    null,                   # unit_price
                    $account->unit_price,   # result_unit_price
                    false,                  # is_locked
                    $transactable,
                    true,                   # status
                    $message
                );
            return $account;
        });
    }

    public function manipulate(
        Account $account,
        User $operator,
        string $type,
        $amount,
        $unit_price = null,
        string $note = null,
        string $message = null
    ) {
        assert(in_array($type, [
            Transaction::TYPE_MANUAL_DEPOSIT,
            Transaction::TYPE_MANUAL_WITHDRAWAL,
        ]));

        $coin_scale = config('core.coin.scale');
        $currency_rate_scale = config('core.currency.rate_scale');
        $amount = (string) Dec::create($amount)->floor($coin_scale);
        if (!is_null($unit_price)) {
            $unit_price = (string) Dec::create($unit_price)->floor($currency_rate_scale);
        }

        DB::transaction(function () use ($account, $operator, $type, $amount, $unit_price, $note, $message) {
            $account = $this->AccountRepo->findForUpdate($account->id);
            $manipulation = $this->ManipulationRepo
                ->create(
                    $operator,
                    $note
                );

            if ($type === Transaction::TYPE_MANUAL_DEPOSIT) {
                # deposit to account
                $this->deposit(
                    $account->user,
                    $account->coin,
                    $amount,
                    Transaction::TYPE_MANUAL_DEPOSIT,
                    $unit_price,
                    $manipulation,
                    $message
                );
            } elseif ($type === Transaction::TYPE_MANUAL_WITHDRAWAL) {
                # withdraw from account
                $this->withdraw(
                    $account->user,
                    $account->coin,
                    $amount,
                    Transaction::TYPE_MANUAL_WITHDRAWAL,
                    $manipulation,
                    $message
                );
            }
        });
    }

    public function calcWithdrawal(
        User $user,
        string $coin,
        string $amount,
        $throw_exception = false
    ) {
        assert(in_array($coin, array_keys($this->coins)));
        $coin_decimal = $this->coins[$coin]['decimal'];
        $amount = Dec::create($amount)->floor($coin_decimal);

        $fee_setting = $this->FeeSettingRepo->getFixed(
            $coin,
            FeeSetting::TYPE_WITHDRAWAL,
            $user->group
        );
        if (is_null($fee_setting)) {
            $fee_setting = $this->FeeSettingRepo->getFixed(
                $coin,
                FeeSetting::TYPE_WITHDRAWAL,
                null
            );
        }
        $fee_amount = $this->FeeService->getWithdrawalFee($coin, $user->group);

        # limitation check
        if (!$this->LimitationRepo->checkLimitation(
            $user,
            Limitation::TYPE_WITHDRAWAL,
            $coin,
            (string) $amount
        )) {
            if ($throw_exception) {
                throw new WithdrawLimitationError;
            } else {
                $out_of_limits = true;
            }
        }

        # user daily withdrawal check
        $args = $this->getDailyWithdrawalLimitationArguments($user);
        $coin_remain = $this->ExchangeService->USDTToCoin($args['daily_remain'], $coin);
        if (Dec::gt($amount, $coin_remain)) {
            if ($throw_exception) {
                throw new WithdrawLimitationError;
            } else {
                $out_of_limits = true;
            }
        }

        # account balance check
        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);
        if ((Dec::add($amount, $fee_amount)->comp($account->available_balance)) === 1) {
            if ($throw_exception) {
                throw new InsufficientBalanceError;
            } else {
                $balance_insufficient = true;
            }
        }

        return [
            'coin' => $coin,
            'amount' => (string) $amount,
            'fee' => $fee_amount,
            'fee_setting' => $fee_setting,
            'out_of_limits' => isset($out_of_limits),
            'balance_insufficient' => isset($balance_insufficient),
        ];
    }

    public function createWithdrawal(
        User $user,
        string $coin,
        string $amount,
        string $address,
        string $tag = null,
        string $message = null
    ) {
        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);
        $calc_result = $this->calcWithdrawal(
            $user,
            $coin,
            $amount,
            true
        );
        return DB::transaction(function () use ($account, $address, $tag, $calc_result, $message) {
            $account = $this->AccountRepo->findForUpdate($account->id);
            # Create withdrawal
            $withdrawal =  $this->WithdrawalRepo->create([
                'user_id' => $account->user_id,
                'account_id' => $account->id,
                'coin' => $account->coin,
                'address' => $address,
                'tag' => $tag,
                'amount' => $calc_result['amount'],
                'fee' => $calc_result['fee'],
                'fee_setting_id' => $calc_result['fee_setting']['id'],
                'message' => $message,
                'expired_at' => millitime(Carbon::now()->addMinute(config('core.withdrawal.timeout'))),
            ]);

            # Update withdrawal callback
            $this->WithdrawalRepo->update($withdrawal, [
                'callback' => $withdrawal->getCallback(),
            ]);

            # transaction
            $amount_with_fee = (string) Dec::add($calc_result['amount'], $calc_result['fee']);
            $account = $this->lock(
                $account->user,
                $account->coin,
                $amount_with_fee,
                Transaction::TYPE_WALLET_WITHDRAWAL_LOCK,
                $withdrawal
            );
            return $withdrawal;
        });
    }

    public function submitWithdrawal(Withdrawal $withdrawal)
    {
        if (
            !$withdrawal->is_confirmed or
            $withdrawal->is_submitted_confirmed or
            $withdrawal->is_canceled
        ) {
            Log::error("submitWithdrawal, try to submit a unconfirmed/submitted_confiremd/canceled withdrawal {$withdrawal->id}.");
            throw new WithdrawalStatusError;
        }

        # Not to submit withdrawal that already tried in one minute.
        if (!is_null($withdrawal->submitted_at)) {
            if (Carbon::now()->subMinute()->lt($withdrawal->submitted_at)) {
                return;
            }
        }

        Log::info("Try to submit withdrawal {$withdrawal->id}");
        # Set withdrawal's submitted_at
        $this->WithdrawalRepo->setSubmitted($withdrawal);

        list($id, $user, $coin, $amount, $address, $tag, $fee, $callback) = [
            $withdrawal->id,
            $withdrawal->user,
            $withdrawal->coin,
            $withdrawal->amount,
            $withdrawal->address,
            $withdrawal->tag,
            $withdrawal->fee,
            $withdrawal->callback,
        ];

        try {
            $response = $this->WalletService->withdrawal(
                $coin,
                $address,
                $tag,
                $amount,
                $callback,
                $id,  # client_id
                true  # is_full_payment
            );
            Log::info("submitWithdrawal. Withdrawal {$id} submitted successfully.", $response);
        } catch (ConflictHttpException $e) {
            # Data alerady exists. Withdrawal must been sent successfully before
            $is_conflict = true;
            Log::error("submitWithdrawal. Withdrawal {$id} submitted conflict.");
        } catch (BadRequestError $e) {
            $this->cancelWithdrawal($withdrawal, Withdrawal::BAD_REQUEST);
            $withdrawal->refresh();
            $withdrawal->user->notify(new WithdrawalBadRequestNotification($withdrawal));
            FcmWithdrawalBadRequestNotification::dispatch($withdrawal->user, $withdrawal)->onQueue(config('services.fcm.queue_name'));
            Log::error("submitWithdrawal. Withdrawal {$id} s BadRequestError, withdrawal canceled. {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            Log::alert("submitWithdrawal. Withdrawal {$id} vendorException or UnknownException. {$e->getMessage()}");
            throw $e;
        }

        # Retrive remote withdrawal data if is conflict situation
        if (isset($is_conflict)) {
            try {
                $response = $this->WalletService->getWithdrawal($coin, null, $id);
            } catch (BadRequestError $e) {
                Log::alert("submitWithdrawal. Get remtoe withdrawal {$id} BadRequestError, withdrawal pending. {$e->getMessage()}");
                return;
            } catch (ModelNotFoundException $e) {
                Log::alert("submitWithdrawal. When submitting {$id}, 409 conflict received but remote records not exists, withdrawal pending.");
                return;
            } catch (\Throwable $e) {
                Log::alert("submitWithdrawal. Get remtoe withdrawal {$id} vendorException or UnknownException, withdrawal pending. {$e->getMessage()}");
                return;
            }
        }

        # Check withdrawal response parameter existence
        $this->WalletService->checkWithdrawalResponseParameter($response);

        # Check local/remote data consistency
        $this->compareWithdrawals($withdrawal, $response, false);

        # Update withdrawal model
        $this->WithdrawalRepo->updateMetadata($withdrawal, $response);

        DB::transaction(function () use ($withdrawal, $user, $coin, $amount, $fee) {
            $account = $this->AccountRepo->findForUpdateByUserCoin($user, $coin);
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($coin);
            if ($fee_coin = data_get($this->coins, "{$coin}.fee_coin")) {
                $wallet_fee_balance = $this->WalletBalanceRepo->findForUpdateByCoin($fee_coin);
            }

            # Set withdrawal's submitted_confirmed_at
            $this->WithdrawalRepo->setSubmittedConfirmed($withdrawal);

            # Update balance and generate transactions
            $this->unlock(
                $account->user,
                $account->coin,
                (string)Dec::add($amount, $fee),
                Transaction::TYPE_WALLET_WITHDRAWAL_UNLOCK,
                $withdrawal
            );
            $this->withdraw(
                $account->user,
                $account->coin,
                $amount,
                Transaction::TYPE_WALLET_WITHDRAWAL,
                $withdrawal
            );

            # wallet balance
            $this->WalletBalanceRepo->withdraw($wallet_balance, $amount);
            $wallet_balance->refresh();

            # wallet balance log
            $this->WalletBalanceLogRepo->create(
                $withdrawal,
                $wallet_balance,
                WalletBalanceLog::TYPE_WITHDRAWAL,
                (string) Dec::create($amount)->additiveInverse()
            );

            if (Dec::create($fee)->isPositive()) {
                $this->withdraw(
                    $account->user,
                    $account->coin,
                    $fee,
                    Transaction::TYPE_WITHDRAWAL_FEE,
                    $withdrawal
                );
            }

            # wallet balance: wallet fee
            if (isset($wallet_fee_balance)) {
                $this->WalletBalanceRepo->withdraw(
                    $wallet_fee_balance,
                    $withdrawal->wallet_fee
                );
                $wallet_fee_balance->refresh();

                # wallet balance log
                $this->WalletBalanceLogRepo->create(
                    $withdrawal,
                    $wallet_fee_balance,
                    WalletBalanceLog::TYPE_WALLET_FEE,
                    (string) Dec::create($withdrawal->wallet_fee)->additiveInverse()
                );
            } else {
                $this->WalletBalanceRepo->withdraw(
                    $wallet_balance,
                    $withdrawal->wallet_fee
                );
                $wallet_balance->refresh();

                # wallet balance log
                $this->WalletBalanceLogRepo->create(
                    $withdrawal,
                    $wallet_balance,
                    WalletBalanceLog::TYPE_WALLET_FEE,
                    (string) Dec::create($withdrawal->wallet_fee)->additiveInverse()
                );
            }
            $this->SystemActionRepo->createByApplicable($withdrawal, [
                'type' => SystemAction::TYPE_SUBMIT_WITHDRAWAL,
                'description' => 'System submit this withdrawal',
            ]);
        });
    }

    public function compareWithdrawals(Withdrawal $withdrawal, array $compared, $is_callback = false)
    {
        $diff = [];
        if (data_get($compared, 'client_id') !== $withdrawal->id) {
            $diff['client_id'] = data_get($compared, 'client_id');
        }
        if (data_get($compared, 'currency') !== $this->wallet_coin_map[$withdrawal->coin]) {
            $diff['currency'] = data_get($compared, 'currency');
        }
        if (strtolower(data_get($compared, 'address')) !== strtolower($withdrawal->address)) {
            $diff['address'] = data_get($compared, 'address');
        }
        if (data_get($compared, 'tag') !== $withdrawal->tag) {
            $diff['tag'] = data_get($compared, 'tag');
        }
        if (data_get($compared, 'is_full_payment') !== true) {
            $diff['is_full_payment'] = data_get($compared, 'is_full_payment');
        }
        if (data_get($compared, 'callback') !== $withdrawal->callback) {
            $diff['callback'] = data_get($compared, 'callback');
        }
        if (Dec::create(data_get($compared, 'amount', 0))->comp($withdrawal->amount) !== 0) {
            $diff['amount'] = data_get($compared, 'amount');
        }
        if ($is_callback) {
            if (data_get($compared, 'id') !== $withdrawal->wallet_id) {
                $diff['id'] = data_get($compared, 'id');
            }
            if (data_get($compared, 'transaction') !== $withdrawal->transaction) {
                $diff['transaction'] = data_get($compared, 'transaction');
            }
        }

        if (!empty($diff)) {
            Log::alert('AccountService/compareWithdrawals, Withdrawal data inconsistent', $diff);
            throw new VendorException('Withdrawal data inconsistent');
        }
    }

    public function cancelWithdrawal(Withdrawal $withdrawal, string $reason)
    {
        $SystemActionRepo = app()->make(SystemActionRepo::class);
        DB::transaction(function () use ($withdrawal, $reason, $SystemActionRepo) {
            $account = $this->AccountRepo->findForUpdateByUserCoin(
                $withdrawal->user,
                $withdrawal->coin
            );
            $this->unlock(
                $account->user,
                $account->coin,
                (string) Dec::add($withdrawal->amount, $withdrawal->fee),
                Transaction::TYPE_WALLET_WITHDRAWAL_CANCELED,
                $withdrawal
            );
            $this->WithdrawalRepo->cancel($withdrawal);
            if ($reason === Withdrawal::BAD_REQUEST) {
                $SystemActionRepo->createByApplicable($withdrawal, [
                    'type' => SystemAction::TYPE_CANCEL_WITHDRAWAL,
                    'description' => 'System cancel this withdrawal due to bad request error',
                ]);
            } else if ($reason === Withdrawal::EXPIRED) {
                $SystemActionRepo->createByApplicable($withdrawal, [
                    'type' => SystemAction::TYPE_CANCEL_WITHDRAWAL,
                    'description' => 'System cancel this withdrawal due to expiration',
                ]);
            }
        });
    }

    public function handleManualWithdrawalCallback(array $values)
    {
        $manipulate = $this->WalletManipulationRepo->findByWalletIdType(data_get($values, 'id'), WalletManipulation::TYPE_WITHDRAWAL);
        if (is_null($manipulate)) {
            Log::alert("AccountService: handleManualWithdrawalCallback manipulate data not found");
            throw new NotFoundHttpException;
        }
        if (!is_null($manipulate->callback_response)) {
            Log::error("handleManualWithdrawalCallback. Manipulate {$manipulate->id} callback received.");
            throw new DuplicateRecordError;
        }

        $response = $manipulate->response;
        if (data_get($response, 'id') !== data_get($values, 'id') or
            data_get($response, 'address') !== data_get($values, 'address') or
            data_get($response, 'amount') !== data_get($values, 'amount') or
            data_get($response, 'src_amount') !== data_get($values, 'src_amount') or
            data_get($response, 'dst_amount') !== data_get($values, 'dst_amount') or
            data_get($response, 'transaction') !== data_get($values, 'transaction') or
            data_get($response, 'currency') !== data_get($values, 'currency') or
            data_get($response, 'fee_currency') !== data_get($values, 'fee_currency') or
            data_get($response, 'is_full_payment') !== data_get($values, 'is_full_payment')
        ) {
            Log::alert(
                'AccountService: handleManualWithdrawalCallback response and callback_response inconsistent',
                ['response' => $response, 'callback_response' => $values]
            );
            throw new VendorException('handleManualWithdrawalCallback/callback parameters inconsistency');
        }
        $fee_currency = data_get($response, 'fee_currency') ?? data_get($response, 'currency');
        $request_fee_currency = data_get($values, 'fee_currency') ?? data_get($values, 'currency');
        if ($fee_currency !== $request_fee_currency) {
            Log::alert(
                'AccountService: handleManualWithdrawalCallback fee_currency inconsistency',
                ['callback_fee_currency' => $request_fee_currency, 'manipulate_fee_currency' => $fee_currency]
            );
            throw new VendorException('handleManualWithdrawalCallback/callback fee_currency inconsistency');
        }
        $fee_coin = data_get($this->wallet_reverse_coin_map, $fee_currency);
        if (is_null($fee_coin)) {
            Log::alert(
                'AccountService: handleManualWithdrawalCallback unsupported coin',
                ['fee_currency' => $fee_currency]
            );
            throw new VendorException('handleManualWithdrawalCallback/callback fee_currency to coin mapping failure');
        }

        DB::transaction(function () use ($manipulate, $response, $values, $fee_coin) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($fee_coin);
            $fee_amount = data_get($values, 'fee', '0');
            if (!Dec::eq($fee_amount, $response['fee'])) {
                $diff = Dec::sub($fee_amount, $response['fee']);
                if ($diff->isPositive()) {
                    $this->WalletBalanceRepo->withdraw($wallet_balance, (string) $diff);
                    $wallet_balance->refresh();
                    $this->WalletBalanceLogRepo->create(
                        $manipulate,
                        $wallet_balance,
                        WalletBalanceLog::TYPE_WALLET_FEE_CORRECTION,
                        (string) Dec::create($diff)->additiveInverse()
                    );
                } else {
                    $diff = (string) Dec::create($diff)->additiveInverse();
                    $this->WalletBalanceRepo->deposit($wallet_balance, $diff);
                    $wallet_balance->refresh();
                    $this->WalletBalanceLogRepo->create(
                        $manipulate,
                        $wallet_balance,
                        WalletBalanceLog::TYPE_WALLET_FEE_CORRECTION,
                        $diff
                    );
                }
            }
            $this->WalletManipulationRepo->updateCallbackResponse($manipulate, $values);
        });
    }

    public function handleWithdrawalCallback(Withdrawal $withdrawal, array $values)
    {
        $this->compareWithdrawals($withdrawal, $values, true);
        $fee_currency = data_get($values, 'fee_currency') ?? data_get($values, 'currency');
        if ($fee_currency !== $withdrawal->wallet_fee_coin) {
            Log::alert(
                'AccountService: handleWithdrawalCallback fee_currency inconsistency',
                ['fee_currency' => $fee_currency, 'withdrawal_fee_coin' => $withdrawal->wallet_fee_coin]
            );
            throw new VendorException('handleWithdrawalCallback/callback fee_currency inconsistency');
        }
        $fee_coin = data_get($this->wallet_reverse_coin_map, $fee_currency);
        if (is_null($fee_coin)) {
            Log::alert(
                'AccountService: handleWithdrawalCallback unsupported coin',
                ['fee_currency' => $fee_currency]
            );
            throw new VendorException('handleWithdrawalCallback/callback fee_currency to coin mapping failure');
        }
        DB::transaction(function () use ($withdrawal, $values, $fee_coin) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($fee_coin);

            $this->WithdrawalRepo->setNotifed($withdrawal);
            $this->WithdrawalRepo->update($withdrawal, ['callback_response' => $values]);

            $fee_amount = data_get($values, 'fee', '0');
            if (!Dec::eq($fee_amount, $withdrawal->wallet_fee)) {
                $diff = Dec::sub($fee_amount, $withdrawal->wallet_fee);
                if ($diff->isPositive()) {
                    $this->WalletBalanceRepo->withdraw($wallet_balance, (string) $diff);
                    $wallet_balance->refresh();
                    $this->WalletBalanceLogRepo->create(
                        $withdrawal,
                        $wallet_balance,
                        WalletBalanceLog::TYPE_WALLET_FEE_CORRECTION,
                        (string) Dec::create($diff)->additiveInverse()
                    );
                } else {
                    $diff = (string) Dec::create($diff)->additiveInverse();
                    $this->WalletBalanceRepo->deposit($wallet_balance, $diff);
                    $wallet_balance->refresh();
                    $this->WalletBalanceLogRepo->create(
                        $withdrawal,
                        $wallet_balance,
                        WalletBalanceLog::TYPE_WALLET_FEE_CORRECTION,
                        $diff
                    );
                }
            }
        });
    }

    public function manualDeposit(array $values)
    {
        if (!$coin = array_search(data_get($values, 'currency'), $this->wallet_coin_map)) {
            Log::alert('AccountService: Manual deposit callback unsupported coin', ['coin' => data_get($values, 'currency')]);
            throw new VendorException;
        }
        if ($this->WalletManipulationRepo->findByWalletIdType(data_get($values, 'id'), WalletManipulation::TYPE_DEPOSIT)) {
            Log::error("Duplicate manual deposit callback", $values);
            throw new DuplicateRecordError;
        }

        return DB::transaction(function () use ($coin, $values) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($coin);

            # create wallet manipulation
            try {
                $manipulate = $this->WalletManipulationRepo->create([
                    'coin' => $coin,
                    'type' => WalletManipulation::TYPE_DEPOSIT,
                    'wallet_id' => data_get($values, 'id'),
                    'transaction' => data_get($values, 'transaction'),
                    'amount' => data_get($values, 'amount'),
                    'callback_response' => $values,
                ]);
            } catch (QueryException $e) {
                Log::error("Duplicate manual deposit callback", $values);
                throw new DuplicateRecordError;
            }

            # wallet balance
            $this->WalletBalanceRepo->deposit($wallet_balance, data_get($values, 'amount'));
            $wallet_balance->refresh();

            # wallet balance log
            $this->WalletBalanceLogRepo->create(
                $manipulate,
                $wallet_balance,
                WalletBalanceLog::TYPE_MANUAL_DEPOSIT,
                data_get($values, 'amount')
            );
            return $manipulate;
        });
    }

    public function createDeposit(User $user, array $values)
    {
        if (!$coin = array_search(data_get($values, 'currency'), $this->wallet_coin_map)) {
            Log::alert('AccountService: Deposit callback unsupported coin', ['coin' => data_get($values, 'currency')]);
            throw new VendorException;
        }

        $account = $this->AccountRepo->findByUserCoinOrFail($user, $coin);

        if (strtolower(data_get($values, 'address')) !== strtolower($account->address)) {
            Log::alert("createDeposit, Deposit callback address inconsistency.", [
                'account_address' => $account->address,
                'deposit_address' => data_get($values, 'address'),
            ]);
            throw new VendorException;
        }

        if ($this->DepositRepo->findByWalletId(data_get($values, 'id'))) {
            Log::error("Duplicate deposit callback", $values);
            throw new DuplicateRecordError;
        }

        return DB::transaction(function () use ($account, $values) {
            $account = $this->AccountRepo->findForUpdate($account->id);
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($account->coin);

            # create deposit
            try {
                $deposit = $this->DepositRepo->create([
                    'user_id' => $account->user_id,
                    'account_id' => $account->id,
                    'wallet_id' => data_get($values, 'id'),
                    'transaction' => data_get($values, 'transaction'),
                    'coin' => $account->coin,
                    'address' => data_get($values, 'address'),
                    'tag' => data_get($values, 'tag'),
                    'amount' => data_get($values, 'amount'),
                    'confirmed_at' => millitime(Carbon::parse(data_get($values, 'confirmed_at'))),
                    'callback_response' => $values,
                ]);
            } catch (QueryException $e) {
                Log::error("Duplicate deposit callback", $values);
                throw new DuplicateRecordError;
            }

            # deposit to account and make transaction
            $this->deposit(
                $account->user,
                $account->coin,
                data_get($values, 'amount'),
                Transaction::TYPE_WALLET_DEPOSIT,
                null,
                $deposit
            );

            # wallet balance
            $this->WalletBalanceRepo->deposit($wallet_balance, data_get($values, 'amount'));
            $wallet_balance->refresh();

            # wallet balance log
            $this->WalletBalanceLogRepo->create(
                $deposit,
                $wallet_balance,
                WalletBalanceLog::TYPE_DEPOSIT,
                data_get($values, 'amount')
            );

            return $deposit;
        });
    }

    public function getWalletAddress(User $user, string $coin)
    {
        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);
        try {
            if (is_null($account->address)) {
                $callback = [
                    'deposit' => $user->wallet_deposit_callback,
                    'payin' => $user->wallet_payin_callback,
                    'payout' => $user->wallet_payout_callback,
                    'approvement' => $user->wallet_approvement_callback,
                ];
                $new_address = $this->WalletService->createAddress($coin, $user->id, $callback);
                $this->AccountRepo->assignAddrTag(
                    $user,
                    $coin,
                    data_get($new_address, 'address'),
                    data_get($new_address, 'tag')
                );
                $account = $account->fresh();
            }
            $result = $this->WalletService->getAddress($account->coin, $account->address, $account->tag);
        } catch (\Throwable $e) {
            throw new ServiceUnavailableError;
        }
        return [
            'user' => $user,
            'coin' => $coin,
            'address' => $result['address'],
            'tag' => $result['tag'],
        ];
    }

    public function updateUserDepositCallbacks(User $user)
    {
        $callback = [
            'deposit' => $user->wallet_deposit_callback,
            'payin' => $user->wallet_payin_callback,
            'payout' => $user->wallet_payout_callback,
            'approvement' => $user->wallet_approvement_callback,
        ];
        $accounts = $this->AccountRepo->allByUser($user);
        $result = [];
        foreach ($accounts as $account) {
            if (!is_null($account->address)) {
                try {
                    Log::info("Update user {$user->id} account {$account->id} deposit callback.");
                    if (!$response = $this->WalletService->updateAddressCallback($account->coin, $account->address, $account->tag, $callback)) {
                        $response = "Update User {$user->id} {$account->coin} account {$account->id} failed.";
                    }
                    $result[] = $response;
                } catch (\Throwable $e) {
                    Log::error("Update user {$user->id} account {$account->id} deposit callback failed.");
                }
            }
        }
        return $result;
    }

    public function handlePayinCallback(array $values)
    {
        $currency = data_get($values, 'fee_currency') ?? data_get($values, 'currency');
        if (!$coin = array_search($currency, $this->wallet_coin_map)) {
            Log::alert('AccountService: Wallet payin callback unsupported coin', ['coin' => data_get($values, 'currency')]);
            throw new VendorException;
        }
        if ($this->WalletLogRepo->findByWalletIdType(data_get($values, 'id'), WalletLog::TYPE_PAYIN)) {
            Log::error("Duplicate wallet payin callback", $values);
            throw new DuplicateRecordError;
        }

        return DB::transaction(function () use ($coin, $values) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($coin);

            $fee = data_get($values, 'fee', '0');
            # create wallet log
            try {
                $log = $this->WalletLogRepo->create([
                    'coin' => $coin,
                    'type' => WalletLog::TYPE_PAYIN,
                    'wallet_id' => data_get($values, 'id'),
                    'address' => data_get($values, 'address'),
                    'fee' => $fee,
                    'callback_response' => $values,
                ]);
            } catch (QueryException $e) {
                Log::error("Duplicate wallet payin callback", $values);
                throw new DuplicateRecordError;
            }

            # wallet balance
            $this->WalletBalanceRepo->withdraw($wallet_balance, $fee);
            $wallet_balance->refresh();

            # wallet balance log
            $this->WalletBalanceLogRepo->create(
                $log,
                $wallet_balance,
                WalletBalanceLog::TYPE_PAYIN,
                (string) Dec::create($fee)->additiveInverse()
            );
            return $log;
        });
    }

    public function handlePayoutCallback(array $values)
    {
        $currency = data_get($values, 'fee_currency') ?? data_get($values, 'currency');
        if (!$coin = array_search($currency, $this->wallet_coin_map)) {
            Log::alert('AccountService: Wallet payout callback unsupported coin', ['coin' => data_get($values, 'currency')]);
            throw new VendorException;
        }
        if ($this->WalletLogRepo->findByWalletIdType(data_get($values, 'id'), WalletLog::TYPE_PAYOUT)) {
            Log::error("Duplicate wallet payout callback", $values);
            throw new DuplicateRecordError;
        }

        return DB::transaction(function () use ($coin, $values) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($coin);

            $fee = data_get($values, 'fee', '0');
            # create wallet log
            try {
                $log = $this->WalletLogRepo->create([
                    'coin' => $coin,
                    'type' => WalletLog::TYPE_PAYOUT,
                    'wallet_id' => data_get($values, 'id'),
                    'address' => data_get($values, 'address'),
                    'fee' => $fee,
                    'callback_response' => $values,
                ]);
            } catch (QueryException $e) {
                Log::error("Duplicate wallet payout callback", $values);
                throw new DuplicateRecordError;
            }

            # wallet balance
            $this->WalletBalanceRepo->withdraw($wallet_balance, $fee);
            $wallet_balance->refresh();

            # wallet balance log
            $this->WalletBalanceLogRepo->create(
                $log,
                $wallet_balance,
                WalletBalanceLog::TYPE_PAYOUT,
                (string) Dec::create($fee)->additiveInverse()
            );
            return $log;
        });
    }

    public function handleApprovementCallback(array $values)
    {
        $currency = data_get($values, 'fee_currency') ?? data_get($values, 'currency');
        if (!$coin = array_search($currency, $this->wallet_coin_map)) {
            Log::alert('AccountService: Wallet approvement callback unsupported coin', ['coin' => data_get($values, 'currency')]);
            throw new VendorException;
        }
        if ($this->WalletLogRepo->findByWalletIdType(data_get($values, 'id'), WalletLog::TYPE_PAYOUT)) {
            Log::error("Duplicate wallet approvement callback", $values);
            throw new DuplicateRecordError;
        }

        return DB::transaction(function () use ($coin, $values) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($coin);

            $fee = data_get($values, 'fee', '0');
            # create wallet log
            try {
                $log = $this->WalletLogRepo->create([
                    'coin' => $coin,
                    'type' => WalletLog::TYPE_APPROVEMENT,
                    'wallet_id' => data_get($values, 'id'),
                    'address' => data_get($values, 'address'),
                    'fee' => $fee,
                    'callback_response' => $values,
                ]);
            } catch (QueryException $e) {
                Log::error("Duplicate wallet payout callback", $values);
                throw new DuplicateRecordError;
            }

            # wallet balance
            $this->WalletBalanceRepo->withdraw($wallet_balance, $fee);
            $wallet_balance->refresh();

            # wallet balance log
            $this->WalletBalanceLogRepo->create(
                $log,
                $wallet_balance,
                WalletBalanceLog::TYPE_APPROVEMENT,
                (string) Dec::create($fee)->additiveInverse()
            );
            return $log;
        });
    }

    public function getDailyWithdrawalLimitationArguments(User $user)
    {
        $daily_max = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_LIMIT, 'daily')
            ?? config('core.withdrawal.limit.daily');

        if ($user->two_factor_auth) {
            $daily_max = (string) Dec::mul($daily_max, config('core.two_factor_auth.withdrawal_limit'), config('core.currency.scale'));
        }

        $tz = config('core.timezone.default');
        $daily_used = $this->calculateWithdrawalValue(
            $user,
            Carbon::today($tz),
            Carbon::tomorrow($tz)
        );

        return [
            'daily_max' => $daily_max,
            'daily_used' => $daily_used,
            'daily_remain' => (string) Dec::sub($daily_max, $daily_used),
        ];
    }

    public function calculateWithdrawalValue(User $user, Carbon $from, Carbon $to)
    {
        $res = Dec::create(0);
        $withdrawals = $this->WithdrawalRepo->getUserUncanceledWithdrawals(
            $user,
            null, # all coins
            $from,
            $to
        );
        foreach ($withdrawals as $w) {
            $res = Dec::add($res, $this->ExchangeService->coinToBaseValue($w->coin, $w->amount));
        }
        return formatted_price((string) $res);
    }
}
