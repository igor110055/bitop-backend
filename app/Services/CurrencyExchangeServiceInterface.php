<?php

namespace App\Services;

interface CurrencyExchangeServiceInterface
{
    public function currencyExchangeRates();
    public function update();
    public function getTWBankCurrency($currency, $side);
    public function getTWBankCurrencies(array $currencies = null);
    public function getHKDtoCNYrate();
    public function fetchCurrencyLayerExchangeRate();
    public function getHuobiUSDTtoCNYrate();
}
