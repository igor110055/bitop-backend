<?php

namespace App\Services;

use App\Models\{
    User,
};

interface ExchangeServiceInterface
{
    public function getTotalAndAmount(
        string $coin,
        string $currency,
        $unit_price,
        $amount = null,
        $total = null
    );
    public function getCoinPriceMap($group = null);
    public function coinToCurrency(User $user, $coin, $currency, $type, $coin_amount = '1');

    public function coinToBaseValue($coin, $amount);

    # coin amount => USDT amount
    /* public function coinToUSDT($coin, $amount); */

    # USDT amount => coin amount
    public function USDTToCoin($USDT_amount, $coin);

    /* public function getAgencyCurrencyPrice(User $user, $currency); */
}
