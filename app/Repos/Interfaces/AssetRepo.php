<?php

namespace App\Repos\Interfaces;

use App\Models\Asset;

interface AssetRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create($asset, $currency);
    public function allByAgency($asset);
    public function allByAgencyOrCreate($asset);
    public function getBalancesSum($currency);
    public function findByAgencyCurrency($asset, $currency);
    public function findByAgencyCurrencyOrFail($asset, $currency);
    public function findByAgencyCurrencyOrCreate($asset, $currency);
    public function deposit($agency, $currency, string $amount, $unit_price = null);
    public function depositByAsset(Asset $asset, string $amount, $unit_price = null);
    public function withdraw($asset, $currency, string $amount);
    public function withdrawByAsset(Asset $asset, string $amount);
}
