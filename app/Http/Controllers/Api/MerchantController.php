<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Database\{
    Eloquent\ModelNotFoundException,
};

use App\Models\{
    Merchant,
};
use App\Repos\Interfaces\{
    MerchantRepo,
};
use App\Services\ExchangeServiceInterface;


class MerchantController extends ApiController
{
    public function __construct(
        MerchantRepo $MerchantRepo
    ) {
        parent::__construct();
        $this->MerchantRepo = $MerchantRepo;
        $this->coins = config('coin');
        $this->currencies = config('currency');
        $this->coin_map = config('services.wallet.coin_map');
    }

    public function getExchangeRates(string $merchant_id)
    {

        $merchant = $this->MerchantRepo->findOrFail($merchant_id);

        $res = app()->make(ExchangeServiceInterface::class)->getMerchantExchangeRates($merchant);
        foreach ($res as $coin => $data) {
            unset($res[$coin]['exchange_rate']);
        }
        return $res;
    }

    public function getExchangeRate(string $merchant_id, $coin)
    {
        if (!in_array($coin, array_keys($this->coins))) {
            throw new ModelNotFoundException;
        }
        $merchant = $this->MerchantRepo->findOrFail($merchant_id);

        $res = app()->make(ExchangeServiceInterface::class)->getMerchantExchangeRate($merchant, $coin);
        unset($res['exchange_rate']);
        return $res;
    }
}
