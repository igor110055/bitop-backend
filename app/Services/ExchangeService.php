<?php

namespace App\Services;

use Dec\Dec;
use App\Models\{
    Advertisement,
    CurrencyExchangeRate,
    User,
};
use App\Repos\Interfaces\{
    AgencyRepo,
    AssetRepo,
    CurrencyExchangeRateRepo,
    CoinExchangeRateRepo,
};

class ExchangeService implements ExchangeServiceInterface
{
    public function __construct(
        AgencyRepo $AgencyRepo,
        AssetRepo $AssetRepo,
        CurrencyExchangeRateRepo $CurrencyRepo,
        CoinExchangeRateRepo $CoinRepo
    ) {
        $this->AgencyRepo = $AgencyRepo;
        $this->AssetRepo = $AssetRepo;
        $this->CurrencyExchangeRateRepo = $CurrencyRepo;
        $this->CoinExchangeRateRepo = $CoinRepo;
        $this->coins = config('coin');
        $this->currencies = config('currency');
        $this->base_currency = config('core.currency.base');
        $this->price_types = CurrencyExchangeRate::PRICE_TYPES;
    }

    public function calculateCoinPrice(
        string $coin,
        $amount,
        $unit_price,
        string $currency
    ) {
        $amount = Dec::create($amount)->floor(config("coin.{$coin}.decimal")); # decimal depends on each coin
        $unit_price = Dec::create($unit_price)->floor(config("core.currency.scale")); # decimal 2
        $price = (string) Dec::mul($unit_price, $amount, config("currency.{$currency}.decimal"));

        return [
            'amount' => (string) $amount,
            'unit_price' => (string) $unit_price,
            'price' => $price,
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

    public function getCoinPrice(
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

    public function getAgencyCurrencyPrice(
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
    }

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
}
