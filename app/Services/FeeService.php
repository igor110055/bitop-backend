<?php

namespace App\Services;

use DB;
use Dec\Dec;
use Carbon\Carbon;
use App\Exceptions\{
    Core\InternalServerError,
    Core\UnexpectedValueError,
    VendorException,
};
use App\Models\{
    Advertisement,
    FeeSetting,
    Group,
    User,
    Config,
    SystemAction,
};
use App\Repos\Interfaces\{
    FeeSettingRepo,
    ShareSettingRepo,
    ConfigRepo,
    FeeCostRepo,
    CoinExchangeRateRepo,
    SystemActionRepo,
    UserRepo,
};

class FeeService implements FeeServiceInterface
{
    public function __construct(
        FeeSettingRepo $FeeSettingRepo,
        ShareSettingRepo $ShareSettingRepo,
        CoinExchangeRateRepo $CoinExchangeRateRepo,
        ConfigRepo $ConfigRepo,
        FeeCostRepo $FeeCostRepo,
        UserRepo $UserRepo
    ) {
        $this->FeeSettingRepo = $FeeSettingRepo;
        $this->ShareSettingRepo = $ShareSettingRepo;
        $this->CoinExchangeRateRepo = $CoinExchangeRateRepo;
        $this->ConfigRepo = $ConfigRepo;
        $this->FeeCostRepo = $FeeCostRepo;
        $this->UserRepo = $UserRepo;
        $this->coins = config('coin');
        $this->currencies = array_keys(config('currency'));
    }

    public function getActiveSettings(
        string $coin,
        string $type,
        $applicable
    ) {
        if ($applicable instanceof User) {
            $applicable = $applicable->group;
        } elseif ($applicable instanceof Group) {
            # do nothing
        } else {
            throw new UnexpectedValueError('First argument should be an User instance');
        }
        if (($fee_settings = $this->FeeSettingRepo->get($coin, $type, $applicable))->isEmpty()) {
            # Get system fee settings if group doesn't have fee settings
            $fee_settings = $this->FeeSettingRepo->get($coin, $type, null);
        }
        return $fee_settings;
    }

    /*
     *  Get the fee and mateched feeSetting according to subject, type and amount
     *
     */

    public function getFee(
        string $type,
        $subject,
        string $coin,
        $coin_amount
    ): array {
        assert(in_array($type, FeeSetting::TYPES));
        $decimal = $this->coins[$coin]['decimal'];
        $default_fee_percentage = config('core.fee.percentage.transaction');

        if ($subject instanceof User) {
            $fee_applicable = $subject->group;
        } else {
            throw new UnexpectedValueError('The second argument sould be User instance');
        }

        $fee_settings = $this->getActiveSettings($coin, $type, $fee_applicable);
        $matched_setting = $this->getMatchedSetting($coin_amount, $fee_settings);

        if (is_null($matched_setting)) {
            $coin_fee_amount = Dec::mul($coin_amount, $default_fee_percentage)->div(100, $decimal);
        } elseif ($matched_setting->unit === '%') {
            $coin_fee_amount = Dec::mul($coin_amount, $matched_setting->value)->div(100, $decimal);
        } elseif ($matched_setting->unit === $coin) {
            $coin_fee_amount = Dec::create($matched_setting->value, $decimal);
        } else {
            throw new UnexpectedValueError('FeeSetting unit error');
        }

        return [
            'coin' => $coin,
            'amount' => (string) $coin_fee_amount, # decimal depends on each coin
            'fee_setting' => $matched_setting,
            'fee_percentage' => trim_zeros(data_get($matched_setting, 'value', $default_fee_percentage)),
        ];
    }

    public function getMatchedSetting(
        $amount,
        $fee_settings
    ) {
        if (!Dec::isPositive($amount)) {
            throw new UnexpectedValueError('Amount should be positive');
        }
        foreach ($fee_settings as $setting) {
            if (is_null($setting->range_end)) {
                return $setting;
            }
            if (Dec::lt($amount, $setting->range_end)) {
                return $setting;
            }
        }
        return null;
    }

    /*
     *  Get the fee shares for each user in the setting and corresponding shareSettings
     *
     * @param $coin Coin of the fee
     * @param $amount Amount of the fee
     * @param $group Group object if the fee is belong to a group
     *
     * @return list of shares include amount, currency, user and corresponding shareSetting
     */
    public function getFeeShares(
        string $coin,
        string $amount,
        User $user
    ): array {
        $decimal = $this->coins[$coin]['decimal'];
        $shareAmount = function ($amount, $percentage) use ($decimal) {
            return $amount->mul($percentage)
                ->div(100)
                ->floor($decimal);
        };

        $percentage = config('core.share.percentage');
        $share_sum = Dec::create(0);
        $share_results = [];
        $amount = Dec::create($amount);
        if (!$amount->isPositive()) {
            return [];
        }

        # Inviter share
        $inviter = $user->inviter;
        if (is_null($inviter)) {
            $inviter = $user;
        }
        $share_amount = $shareAmount($amount, $percentage['inviter']);
        $share_results[] = [
            'user' => $inviter,
            'amount' => $share_amount,
        ];
        $share_sum = $share_sum->add($share_amount);

        # Group owner share
        $group_owner = $user->group->owner;
        if (is_null($group_owner)) {
            $system_user = $this->UserRepo->find(config('core.system_user_id'));
            $group_owner = $system_user;
        }
        if (is_null($group_owner)) {
            $group_owner = $user;
        }
        $share_amount = $shareAmount($amount, $percentage['group_owner']);
        $share_results[] = [
            'user' => $group_owner,
            'amount' => $share_amount,
        ];
        $share_sum = $share_sum->add($share_amount);

        # System share
        $system_share_amount = $amount->sub($share_sum);

        $system_user = $this->UserRepo->find(config('core.system_user_id'));
        if (is_null($system_user)) {
            $root = $this->UserRepo->find(config('core.root_id'));
            $system_user = $root;
        }
        if (is_null($system_user)) {
            $system_user = $user;
        }
        $share_results[] = [
            'user' => $system_user,
            'amount' => $system_share_amount,
        ];
        $share_sum = $share_sum->add($system_share_amount);

        if (empty($share_results)) {
            throw new InternalServerError('Share results are empty');
        }

        return $share_results;
    }

    /* public function getFeeShares(
        string $coin,
        string $amount,
        Group $group = null
    ): array {
        $decimal = $this->coins[$coin]['decimal'];
        $shareAmount = function ($amount, $percentage) use ($decimal) {
            return $amount->mul($percentage)
                ->div(100)
                ->floor($decimal);
        };
        $shareResult = function ($share_setting, $share_amount) use ($coin) {
            return [
                'user' => $share_setting->user,
                'coin' => $coin,
                'amount' => (string)$share_amount,
                'share_setting' => $share_setting,
            ];
        };

        $amount = Dec::create($amount);
        if (!$amount->isPositive()) {
            return [];
        }

        $share_sum = Dec::create(0);
        $share_results = [];

        # Prior shares
        $prior_share_compositions = $this->ShareSettingRepo->getComposition(null, true, false);
        $prior_share_settings = $prior_share_compositions['share_settings'];
        $has_remains = !Dec::eq($prior_share_compositions['total_percentage'], 100);
        foreach ($prior_share_settings as $setting) {
            $share_amount = $shareAmount($amount, $setting['percentage']);
            $share_results[] = $shareResult($setting, $share_amount);
            $share_sum = $share_sum->add($share_amount);
        }

        # Group shares
        $group_share_amount = $amount->sub($share_sum);

        if ($has_remains and $group and $group_share_amount->isPositive()) {
            $group_share_compositions = $this->ShareSettingRepo->getComposition($group, false, false);
            $group_share_settings = $group_share_compositions['share_settings'];
            $has_remains = !Dec::eq($group_share_compositions['total_percentage'], 100);
            foreach ($group_share_settings as $setting) {
                $share_amount = $shareAmount($group_share_amount, $setting['percentage']);
                $share_results[] = $shareResult($setting, $share_amount);
                $share_sum = $share_sum->add($share_amount);
            }
        }

        # System shares
        $system_share_amount = $amount->sub($share_sum);

        if ($has_remains and $system_share_amount->isPositive()) {
            $system_share_compositions = $this->ShareSettingRepo->getComposition(null, false, false);
            $system_share_settings = $system_share_compositions['share_settings'];
            if ($system_share_settings->isEmpty() or !Dec::eq($system_share_compositions['total_percentage'], 100)) {
                throw new InternalServerError('system share sum is not 100%');
            }

            foreach ($system_share_settings as $setting) {
                $share_amount = $shareAmount($system_share_amount, $setting['percentage']);
                $share_results[] = $shareResult($setting, $share_amount);
                $share_sum = $share_sum->add($share_amount);
            }
        }

        if (empty($share_results)) {
            throw new InternalServerError('Share results are empty');
        }

        # handle share amount floor rounding left over
        if (!Dec::eq($amount, $share_sum)) {
            $diff = $amount->sub($share_sum);
            $share_results[0]['amount'] = (string) Dec::add($share_results[0]['amount'], $diff);
        }

        return $share_results;
    } */

    public function updateFeeCost()
    {
        $tz = config('core.timezone.default');
        $coin_map = config('services.wallet.reverse_coin_map');
        $date = Carbon::now($tz)->toDateString();
        $WalletService = app()->make(WalletServiceInterface::class);
        $SystemActionRepo = app()->make(SystemActionRepo::class);
        foreach (array_keys($this->coins) as $coin) {
            $res = $WalletService->getWithdrawalStats($coin);
            $this->checkWalletResponse($res);
            $info = [
                'date' => $date,
                'coin' => $coin,
                'params' => $res,
            ];
            if ($coin === 'ETH' or $coin === 'USDT-ERC20') {
                $info['cost'] = $this->costETHFormula(
                    $coin,
                    data_get($info, 'params.gas_price_1dsma'),
                    data_get($info, 'params.gas_payout_50avg'),
                    data_get($info, 'params.gas_withdrawal_50avg')
                );
            } else if ($coin === 'TRX' or $coin === 'USDT-TRC20') {
                $info['cost'] = $this->costTRXFormula(
                    $coin,
                    data_get($info, 'params.fee_payout_50avg'),
                    data_get($info, 'params.fee_withdrawal_50avg')
                );
            } else if ($coin === 'BTC') {
                $info['cost'] = $this->costBTCFormula(
                    data_get($info, 'params.feerate'),
                    data_get($info, 'params.txbytes_withdrawal_50avg')
                );
            } else {
                continue;
            }
            $data[] = $info;

        }
        DB::transaction(function () use ($data, $SystemActionRepo) {
            foreach ($data as $row) {
                $this->FeeCostRepo->create($row);
            }
            $SystemActionRepo->create([
                'type' => SystemAction::TYPE_UPDATE_WITHDRAWAL_FEE_COST,
                'description' => 'System update withdrawal fee costs',
            ]);
        });
        return $data;
    }

    # fee_rate(satoshi/byte) * 10^(-8)(btc/satoshi) * txbytes_withdrawal_gas
    protected function costBTCFormula($fee_rate, $txbytes_withdrawal_gas)
    {
        return (string) Dec::mul($fee_rate, pow(10, -8))->mul($txbytes_withdrawal_gas);
    }

    # fee_payout  * (payout/withdrawal ratio) + fee_withdrawal
    protected function costTRXFormula($coin, $fee_payout, $fee_withdrawal)
    {
        $pw_ratio = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, "$coin.pw_ratio");
        return (string) Dec::mul($fee_payout, $pw_ratio)->add($fee_withdrawal);

    }

    # (gas_price(wei/gas) * 10^(-18)(eth/wei)) * (payout_gas * (payout/withdrawal ratio) + withdrawal_gas)
    protected function costETHFormula($coin, $gas_price, $payout_gas, $withdrawal_gas)
    {
        $pw_ratio = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, "$coin.pw_ratio");
        $gas_price = Dec::mul($gas_price, pow(10, -18));
        $gas_total = Dec::mul($payout_gas, $pw_ratio)->add($withdrawal_gas);
        return (string) Dec::mul($gas_price, $gas_total);
    }

    public function getWithdrawalFee($coin, $applicable = null)
    {
        $decimal = $this->coins[$coin]['fee_decimal'];
        $cost = data_get($this->FeeCostRepo->getLatest($coin), 'cost', 0);
        if ($fee_coin = data_get($this->coins, "{$coin}.fee_coin")) {
            $fee_coin_price = $this->CoinExchangeRateRepo->getLatest($fee_coin)->price;
            $coin_price = $this->CoinExchangeRateRepo->getLatest($coin)->price;
            $cost = Dec::mul($cost, $fee_coin_price)->div($coin_price);
        }
        $base = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, "$coin.base");
        if ($applicable) {
            $fee_setting = $this->FeeSettingRepo->getFixed($coin, FeeSetting::TYPE_WITHDRAWAL, $applicable);
            if (is_null($fee_setting)) {
                $discount = data_get($this->FeeSettingRepo->getFixed($coin, FeeSetting::TYPE_WITHDRAWAL, null), 'value', 100);
            } else {
                $discount = $fee_setting->value;
            }
        } else {
            $discount = data_get($this->FeeSettingRepo->getFixed($coin, FeeSetting::TYPE_WITHDRAWAL, $applicable), 'value', 100);
        }
        return (string) Dec::mul($base, $discount)->div(100)->add($cost, $decimal);
    }

    protected function checkWalletResponse($response)
    {
        $coin_map = config('services.wallet.reverse_coin_map');
        $erc_eth_required = [
            'gas_price_1dsma',
            'gas_price_7dsma',
            'gas_price_21dsma',
            'gas_payout_50avg',
            'gas_withdrawal_50avg',
        ];
        $btc_required = [
            'feerate',
            'txbytes_withdrawal_50avg',
        ];
        $trc_trx_required = [
            'fee_withdrawal_50avg',
            'fee_payout_50avg',
        ];
        try {
            if (is_null(data_get($response, 'currency'))) {
                throw new VendorException;
            }
            if (data_get($coin_map, $response['currency']) === 'BTC') {
                if (!array_keys_exists($btc_required, $response)) {
                    throw new VendorException;
                }
            } elseif (data_get($coin_map, $response['currency']) === 'ETH' or data_get($coin_map, $response['currency']) === 'USDT-ERC20') {
                if (!array_keys_exists($erc_eth_required, $response)) {
                    throw new VendorException;
                }
            } elseif (data_get($coin_map, $response['currency']) === 'TRX' or data_get($coin_map, $response['currency']) === 'USDT-TRC20') {
                if (!array_keys_exists($trc_trx_required, $response)) {
                    throw new VendorException;
                }
            }
        } catch (VendorException $e) {
            \Log::alert('CheckGetWithdrawalStats. required keys missing in response.', $response);
            throw new VendorException('Invalid wallet withdrawal stats api response');
        }
    }
}
