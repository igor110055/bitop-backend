<?php

namespace App\Repos\DB;

use App\Models\{
    Merchant,
    ExchangeRate,
};

use Carbon\Carbon;

class MerchantRepo implements \App\Repos\Interfaces\MerchantRepo
{
    protected $merchant;

    public function __construct(Merchant $merchant) {
        $this->merchant = $merchant;
        $this->coins = config('coin');
    }

    public function getAllMerchants()
    {
        return $this->merchant->all();
    }

    public function find($id)
    {
        return $this->merchant->find($id);
    }

    public function findOrFail($id)
    {
        return $this->merchant->findOrFail($id);
    }

    public function update(Merchant $merchant, $values)
    {
        return $merchant->update($values);
    }

    public function create($values)
    {
        return $this->merchant->create($values);
    }

    public function createExchangeRate(Merchant $merchant, $data)
    {
        return $merchant->exchange_rates()->create($data);
    }

    public function getLatestExchangeRate(Merchant $merchant, $coin)
    {
        return $merchant->exchange_rates()
            ->where('coin', $coin)
            ->latest()
            ->first();
    }
}
