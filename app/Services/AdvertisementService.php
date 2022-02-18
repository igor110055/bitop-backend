<?php

namespace App\Services;

use DB;
use Dec\Dec;

use Illuminate\Validation\ValidationException;

use App\Exceptions\{
    Core\BadRequestError,
    AdTotalPriceBelowMinLimit,
};
use App\Models\{
    Advertisement,
    Config,
    FeeSetting,
    Order,
    Transaction,
    User,
};
use App\Repos\Interfaces\{
    AccountRepo,
    AdvertisementRepo,
    BankAccountRepo,
    ConfigRepo,
};

class AdvertisementService implements  AdvertisementServiceInterface
{
    public function __construct(
        AccountRepo $AccountRepo,
        AdvertisementRepo $AdvertisementRepo,
        BankAccountRepo $BankAccountRepo,
        ConfigRepo $ConfigRepo,
        AccountServiceInterface $AccountService,
        ExchangeServiceInterface $ExchangeService,
        FeeServiceInterface $FeeService
    ) {
        $this->AccountRepo = $AccountRepo;
        $this->AdvertisementRepo = $AdvertisementRepo;
        $this->AccountService = $AccountService;
        $this->BankAccountRepo = $BankAccountRepo;
        $this->ConfigRepo = $ConfigRepo;
        $this->ExchangeService = $ExchangeService;
        $this->FeeService = $FeeService;
    }

    public function getPriceSpreadPercentage(
        User $user,
        $type,
        $coin,
        $currency,
        $unit_price
    ) {
        $coin_market_price = $this->ExchangeService
            ->coinToCurrency(
                $user,
                $coin,
                $currency,
                $type
            );

        $spread_percentage = Dec::sub($unit_price, $coin_market_price['unit_price'])
            ->abs()
            ->div($coin_market_price['unit_price'])
            ->mul(100, 0);
        return (string) $spread_percentage;
    }

    public function preview(
        User $user,
        $type,
        $coin,
        $currency,
        $unit_price,
        $amount
    ) {
        $decimal = config('core.coin.default_exp');

        if (Dec::create($amount)->equals('0')) {
            $fee_amount = (string) Dec::fromString("0", config("coin.{$coin}.decimal"));
            $fee_percentage = '0';
            $fulfill_amount = $amount;
        } else {

            if ($type === Advertisement::TYPE_SELL) {
                $fee = $this->FeeService->getFee(
                        FeeSetting::TYPE_ORDER,
                        $user,
                        $coin,
                        $amount
                    );
                $fee_amount = $fee['amount'];
                $fee_percentage = $fee['fee_percentage'];

                $fulfill_percentage = bcadd(100, $fee_percentage, 6);
                $fulfill_amount = trim_zeros(bcdiv((string) $amount * 100, $fulfill_percentage, $decimal));

            } else {
                $fee_amount = (string) Dec::fromString("0", config("coin.{$coin}.decimal"));
                $fee_percentage = '0';
                $fulfill_amount = $amount;
            }
        }

        $normalized = $this->ExchangeService->getTotalAndAmount(
            $coin,
            $currency,
            $unit_price,
            $amount,
            null # total
        );

        return [
            'type' => $type,
            'coin' => $coin,
            'amount' => $normalized['amount'],
            'currency' => $currency,
            'unit_price' => $normalized['unit_price'],
            'total' => $normalized['total'],
            'fee' => $fee_amount,
            'fee_percentage' => $fee_percentage,
            'fulfill_amount' => $fulfill_amount,
        ];
    }

    public function make(
        User $user,
        $values,
        array $payables,
        Advertisement $ref = null
    ) {
        # check payment window
        if ($values['is_express']) {
            $values['payment_window'] = $this->ConfigRepo->get(Config::ATTRIBUTE_EXPRESS_PAYMENT_WINDOW);
        } else {
            $payment_window_range = $this->ConfigRepo->get(Config::ATTRIBUTE_PAYMENT_WINDOW);
            if ((data_get($values, 'payment_window', 0) < $payment_window_range['min']) or
                (data_get($values, 'payment_window', 0) > $payment_window_range['max'])) {
                throw ValidationException::withMessages(['payment_window' => 'payment_window is out of range.']);
            }
        }

        # check min amount
        if (Dec::lt(Dec::mul($values['amount'], $values['unit_price']), $values['min_limit'])) {
            throw new AdTotalPriceBelowMinLimit;
        }

        # make sure user has coin account
        $account = $this->AccountRepo
            ->findByUserCoinOrCreate(
                data_get($user, 'id', $user),
                $values['coin']
            );

        # calculate price
        $normalized = $this->ExchangeService->getTotalAndAmount(
            $values['coin'],
            $values['currency'],
            $values['unit_price'],
            $values['amount'],
            null # total
        );

        # check min_limit
        if (Dec::lt($normalized['total'], $values['min_limit'])) {
            throw new AdTotalPriceBelowMinLimit;
        }

        # check max_limit
        if (Dec::lt($normalized['total'], $values['max_limit'])) {
            throw new BadRequestError('Max limit exceed total');
        }

        $values = array_merge($values, $normalized);

        # Calculate fee for sell advertisement
        if ($values['type'] === Advertisement::TYPE_SELL) {
            $fee = $this->FeeService->getFee(
                FeeSetting::TYPE_ORDER,
                $user,
                $values['coin'],
                $values['amount']
            );
            $values['fee'] = $fee['amount'];
            $values['fee_setting_id'] = data_get($fee['fee_setting'], 'id');
        } else {
            $values['fee'] = 0;
            $values['fee_setting_id'] = null;
        }
        $values['user_id'] = $user->id;

        return DB::transaction(function () use ($user, $values, $payables, $ref) {
            # Create advertisement
            if (is_null($ref)) {
                $advertisement = $this->AdvertisementRepo
                    ->create($values);
            } else {
                $this->AdvertisementRepo->delete($ref);
                $advertisement = $this->AdvertisementRepo->createByRef($ref, $values);
            }

            if (!$advertisement->is_express) {
                # Attach bank_accounts
                $bank_account_ids = data_get($payables, Order::PAYABLE_BANK_ACCOUNT, []);
                $filtered_bank_accounts = $this->BankAccountRepo
                    ->filterWithIds($bank_account_ids, [
                        'currency' => $advertisement->currency,
                        'user_id' => $user->id,
                    ]);

                if (empty($filtered_bank_accounts)) {
                    throw new BadRequestError('No valid bank_account provided.');
                }
                # Attach bank_accounts
                $advertisement->bank_accounts()->attach($filtered_bank_accounts->pluck('id'));

                # Update ad's nationality
                $bank_accounts_nationalities = json_encode(array_unique($filtered_bank_accounts->pluck('bank.nationality')->toArray()));
                $this->AdvertisementRepo->setAttribute($advertisement, ['nationality' => $bank_accounts_nationalities]);
            }

            # Lock balance for sell advertisement
            if ($values['type'] === Advertisement::TYPE_SELL) {
                $locked_amount = (string)Dec::add($values['amount'], $values['fee']);
                $this->AccountService
                    ->lock(
                        $user,
                        $values['coin'],
                        $locked_amount,
                        Transaction::TYPE_ACTIVATE_ADVERTISEMENT,
                        $advertisement
                    );
            }

            return $advertisement;
        });
    }

    public function deactivate(
        User $user,
        Advertisement $advertisement
    ) {
        if ($advertisement->status !== Advertisement::STATUS_AVAILABLE) {
            throw new BadRequestError;
        }
        return DB::transaction(function () use ($user, $advertisement) {

            $this->AdvertisementRepo->setStatus(
                $advertisement,
                Advertisement::STATUS_UNAVAILABLE,
                Advertisement::STATUS_AVAILABLE
            );

            if ($advertisement->type === Advertisement::TYPE_SELL) {
                $locked_amount = (string)Dec::add($advertisement->remaining_amount, $advertisement->remaining_fee);

                $this->AccountService
                    ->unlock(
                        $user,
                        $advertisement->coin,
                        $locked_amount,
                        Transaction::TYPE_DEACTIVATE_ADVERTISEMENT,
                        $advertisement
                    );
            }
            return $advertisement;
        });
    }

    public function delete(
        User $user,
        Advertisement $advertisement
    ) {
        if (!in_array($advertisement->status, [
                Advertisement::STATUS_AVAILABLE,
                Advertisement::STATUS_UNAVAILABLE,
            ])) {
            throw new BadRequestError;
        }
        return DB::transaction(function () use ($user, $advertisement) {
            if (($advertisement->status === Advertisement::STATUS_AVAILABLE) and
                ($advertisement->type === Advertisement::TYPE_SELL)) {
                    $locked_amount = (string)Dec::add($advertisement->remaining_amount, $advertisement->remaining_fee);

                    $this->AccountService
                        ->unlock(
                            $user,
                            $advertisement->coin,
                            $locked_amount,
                            Transaction::TYPE_DEACTIVATE_ADVERTISEMENT,
                            $advertisement
                        );
            }
            return $this->AdvertisementRepo->delete($advertisement);
        });
    }
}
