<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Merchant,
    ExchangeRate,
};

interface MerchantRepo
{
    public function getAllMerchants();
    public function find($id);
    public function findOrFail($id);
    public function update(Merchant $merchant, $values);
    public function create($values);
    public function createExchangeRate(Merchant $mecchant, $data);
    public function getLatestExchangeRate(Merchant $merchant, $coin);
}
