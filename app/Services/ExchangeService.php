<?php

namespace App\Services;

use Dec\Dec;
use App\Models\{
    Advertisement,
    CurrencyExchangeRate,
    Merchant,
    ExchangeRate,
    User,
};
use App\Repos\Interfaces\{
    AgencyRepo,
    AssetRepo,
    CurrencyExchangeRateRepo,
    CoinExchangeRateRepo,
    MerchantRepo,
};

class ExchangeService implements ExchangeServiceInterface
{
    public function __construct(
        AgencyRepo $AgencyRepo,
        AssetRepo $AssetRepo,
        CurrencyExchangeRateRepo $CurrencyRepo,
        CoinExchangeRateRepo $CoinRepo,
        MerchantRepo $MerchantRepo
    ) {
        $this->AgencyRepo = $AgencyRepo;
        $this->AssetRepo = $AssetRepo;
        $this->CurrencyExchangeRateRepo = $CurrencyRepo;
        $this->CoinExchangeRateRepo = $CoinRepo;
        $this->MerchantRepo = $MerchantRepo;
        $this->coins = config('coin');
        $this->currencies = config('currency');
        $this->base_currency = config('core.currency.base');
        $this->price_types = CurrencyExchangeRate::PRICE_TYPES;
    }

    public function getTotalAndAmount(
        string $coin,
        string $currency,
        $unit_price,
        $amount = null,
        $total = null
    ) {
        $unit_price = Dec::create($unit_price)->floor(config("core.currency.scale")); # decimal 2
        if (!is_null($amount)) {
            $amount = trim_redundant_decimal($amount, $coin);
            $total = (string) Dec::mul($unit_price, $amount, $this->currencies[$currency]['decimal']);
        } else {
            $total = currency_trim_redundant_decimal($total, $currency);
            $amount = (string) Dec::div($total, $unit_price, $this->coins[$coin]['decimal']);
        }
        return [
            'total' => (string) $total,
            'amount' => (string) $amount,
            'unit_price' => (string) $unit_price,
        ];
    }

    public function getCoinPriceMap($group = null)
    {
        $result = [];
        foreach (array_keys($this->coins) as $coin) {
            foreach (array_keys($this->currencies) as $currency) {
                foreach ($this->price_types as $price_type) {
                    $result[$coin][$currency][$price_type] = $this->getCoinPrice($coin, $currency, $price_type, $group)['unit_price'];
                }
            }
        }
        return $result;
    }

    protected function getCoinPrice(
        $coin,
        $currency,
        $price_type = 'mid',
        $group = null
    ) {
        assert(in_array($coin, array_keys($this->coins)));
        assert(in_array($currency, array_keys($this->currencies)));
        assert(in_array($price_type, $this->price_types));

        $decimal = config('core.currency.scale');

        # base currency
        $unit_price_base = $this->CoinExchangeRateRepo
            ->getLatest($coin)
            ->price;

        $currency_rate = $this->CurrencyExchangeRateRepo
            ->getLatest($currency, $group)
            ->$price_type;

        $unit_price = (string) Dec::mul($unit_price_base, $currency_rate, $decimal);

        return [
            'coin' => $coin,
            'currency' => $currency,
            'price_type' => $price_type,
            'group' => $group,
            'unit_price' => $unit_price, # decimal 2
        ];
    }

    public function coinToCurrency(
        User $user,
        $coin,
        $currency,
        $type,
        $coin_amount = '1'
    ) {
        $price_decimal = $this->currencies[$currency]['decimal'];
        $coin_decimal = $this->coins[$coin]['decimal'];
        $coin_amount = Dec::create($coin_amount)->floor($coin_decimal);

        if ($type === Advertisement::TYPE_BUY) {
            $price_type = 'bid';
        } elseif ($type === Advertisement::TYPE_SELL) {
            $price_type = 'ask';
        } else {
            $price_type = 'mid';
        }

        $coin_price = $this->getCoinPrice(
            $coin,
            $currency,
            $price_type,
            $user->group
        );

        # decimal depends on each currency
        $price = (string) Dec::mul($coin_price['unit_price'], $coin_amount, $price_decimal);

        return [
            'coin' => $coin,
            'coin_amount' => (string) $coin_amount,
            'currency' => $currency,
            'currency_amount' => $price,
            'type' => $type,
            'unit_price' => $coin_price['unit_price'],
        ];
    }

    /* public function getAgencyCurrencyPrice(
        User $user,
        $currency
    ) {
        assert(in_array($currency, array_keys($this->currencies)));

        if ($user->is_agent) {
            $agency = $user->agency;
        } else {
            $agency = $this->AgencyRepo
                ->getDefaultAgency();
        }

        if ($agency) {
            $asset = $this->AssetRepo
                ->findByAgencyCurrency($agency, $currency);
            return data_get($asset, 'unit_price');
        }
        return null;
    } */

    public function coinToBaseValue($coin, $amount)
    {
        $price = $this->CoinExchangeRateRepo->getLatest($coin)->price;
        return (string) Dec::mul($amount, $price, $this->currencies[$this->base_currency]['decimal']);
    }

    public function coinToUSDT($coin, $amount)
    {
        if (data_get($this->coins, "{$coin}.base") === 'USDT') {
            return (string) $amount;
        }
        $coin_price = $this->CoinExchangeRateRepo->getLatest($coin)->price;
        $USDT_price = $this->CoinExchangeRateRepo->getLatest('USDT-ERC20')->price;
        return (string) Dec::mul($amount, $coin_price)->div($USDT_price, $this->coins['USDT-ERC20']['decimal']);
    }

    public function USDTToCoin($USDT_amount, $coin)
    {
        if (data_get($this->coins, "{$coin}.base") === 'USDT') {
            return (string) $USDT_amount;
        }
        $coin_price = $this->CoinExchangeRateRepo->getLatest($coin)->price;
        $USDT_price = $this->CoinExchangeRateRepo->getLatest('USDT-ERC20')->price;
        return (string) Dec::div($USDT_amount, $coin_price)->mul($USDT_price, $this->coins[$coin]['decimal']);
    }

    public function getMerchantExchangeRate(Merchant $merchant, $coin)
    {
        $currency = config('core.currency.base');
        $decimal = config('core.currency.scale');
        assert(in_array($coin, array_keys($this->coins)));

        $exchange_rate = $this->MerchantRepo->getLatestExchangeRate($merchant, $coin);

        if (empty($exchange_rate) or data_get($exchange_rate, 'type') === ExchangeRate::TYPE_SYSTEM) {
            # Get system exchange rate
            list($bid, $ask) = $this->get_system_exchange_rate($coin, $currency);
            $exchange_rate['type'] = ExchangeRate::TYPE_SYSTEM;
        } elseif ($exchange_rate['type'] === ExchangeRate::TYPE_FIXED) {
            $bid = (string) Dec::mul($exchange_rate['bid'], '1', $decimal);
            $ask = (string) Dec::mul($exchange_rate['ask'], '1', $decimal);
        } elseif ($exchange_rate['type'] === ExchangeRate::TYPE_FLOATING) {
            list($bid, $ask) = $this->get_system_exchange_rate($coin, $currency);
            $bid = (string) Dec::add($bid, $exchange_rate['bid_diff'], $decimal);
            $ask = (string) Dec::add($ask, $exchange_rate['ask_diff'], $decimal);
        } elseif ($exchange_rate['type'] === ExchangeRate::TYPE_DIFF) {
            list($bid, $ask) = $this->get_system_exchange_rate($coin, $currency);
            if (Dec::create($ask)->comp($bid) >= 0) {
                $ask = (string) Dec::add($ask, $exchange_rate['diff'], $decimal);
            } else {
                $ask = (string) Dec::add($bid, $exchange_rate['diff'], $decimal);
            }
            $bid = (string) Dec::mul($bid, '1', $decimal);
        }
        return [
            'bid' => $bid,
            'ask' => $ask,
            'coin' => $coin,
            'currency' => $currency,
            'type' => $exchange_rate['type'],
            'exchange_rate' => $exchange_rate,
        ];
    }

    public function getMerchantExchangeRates(Merchant $merchant)
    {
        $coins = $this->coins;
        foreach (array_keys($this->coins) as $coin) {
            $rates[$coin] = $this->getMerchantExchangeRate($merchant, $coin);
        }
        return $rates;
    }

    public function get_system_exchange_rate($coin, $currency)
    {
        $decimal = config('core.currency.scale');

        $unit_price_base = $this->CoinExchangeRateRepo
            ->getLatest($coin)
            ->price;

        $currency_rate = $this->CurrencyExchangeRateRepo
            ->getLatest($currency, null);

        $bid = (string) Dec::mul($unit_price_base, $currency_rate['bid'], $decimal);
        $ask = (string) Dec::mul($unit_price_base, $currency_rate['ask'], $decimal);

        return [$bid, $ask];
    }
}
