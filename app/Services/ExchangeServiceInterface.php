<?php

namespace App\Services;

use App\Models\{
    User,
};

interface ExchangeServiceInterface
{
    public function calculateCoinPrice(
        string $coin,
        $amount,
        $unit_price,
        string $currency
    );
    public function getCoinPriceMap($group = null);
    public function getCoinPrice($coin, $currency, $price_type, $group = null);
    public function coinToCurrency(User $user, $coin, $currency, $type, $coin_amount = '1');

    public function coinToBaseValue($coin, $amount);

    # coin amount => USDT amount
    public function coinToUSDT($coin, $amount);

    # USDT amount => coin amount
    public function USDTToCoin($USDT_amount, $coin);

    public function getAgencyCurrencyPrice(User $user, $currency);
}
