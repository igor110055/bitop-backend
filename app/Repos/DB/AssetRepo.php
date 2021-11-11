<?php

namespace App\Repos\DB;

use Dec\Dec;
use Throwable;
use DB;

use App\Exceptions\{
    Core\BadRequestError,
    Core\UnknownError,
};

use App\Models\{
    Asset,
};

class AssetRepo implements \App\Repos\Interfaces\AssetRepo
{
    protected $asset;

    public function __construct(Asset $asset) {
        $this->asset = $asset;
        $this->currencies = config('core.currency.all');
    }

    public function find($id)
    {
        return $this->asset->find($id);
    }

    public function findOrFail($id)
    {
        return $this->asset->findOrFail($id);
    }

    public function create($agency, $currency)
    {
        assert(in_array($currency, $this->currencies));

        $asset = $this->asset
            ->create([
                'agency_id' => data_get($agency, 'id', $agency),
                'currency' => $currency,
            ])->fresh();

        return $asset;
    }

    public function allByAgency($agency)
    {
        return $this->asset
            ::where('agency_id', data_get($agency, 'id', $agency))
            ->get();
    }

    public function allByAgencyOrCreate($agency)
    {
        $assets = collect([]);

        foreach ($this->currencies as $currency) {
            $asset = $this->findByAgencyCurrencyOrCreate($agency, $currency);
            $assets->push($asset);
        }
        return $assets;
    }

    public function getBalancesSum($currency)
    {
        return $this->asset
            ->where('currency', $currency)
            ->sum('balance');
    }

    protected function getQuery($agency, $currency)
    {
        return $this->asset
            ->where('agency_id', data_get($agency, 'id', $agency))
            ->where('currency', $currency);
    }

    public function findByAgencyCurrency($agency, $currency)
    {
        return $this->getQuery($agency, $currency)->first();
    }

    public function findByAgencyCurrencyOrFail($agency, $currency)
    {
        return $this->getQuery($agency, $currency)->firstOrFail();
    }

    public function findByAgencyCurrencyOrCreate($agency, $currency)
    {
        if ($asset = $this->findByAgencyCurrency($agency, $currency)) {
            return $asset;
        }
        try {
            return $this->create($agency, $currency);
        } catch (Throwable $e) {
            throw $e;
            return $this->findByAgencyCurrencyOrFail($agency, $currency);
        }
    }

    public function deposit($agency, $currency, string $amount, $unit_price = null)
    {
        $asset = $this->findByAgencyCurrencyOrCreate($agency, $currency);
        return $this->depositByasset($asset, $amount, $unit_price);
    }

    public function depositByAsset(Asset $asset, string $amount, $unit_price = null)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        $asset = $this->asset
            ->lockForUpdate()
            ->find($asset->id);
        $update = ['balance' => DB::raw("balance + $amount")];

        if (isset($unit_price)) {
            if (Dec::create($unit_price)->isNegative()) {
                throw new BadRequestError('unit_price must be non-negative');
            }
            $scale = config('core.currency.rate_scale');

            if (is_null($asset->unit_price)) {
                $update['unit_price'] = (string)Dec::create($unit_price, $scale);
            } else {
                $update['unit_price'] = (string)Dec::mul($asset->balance, $asset->unit_price)
                    ->add(Dec::mul($amount, $unit_price))
                    ->div(Dec::add($asset->balance, $amount), $scale);
            }
        }

        if ($this->asset
            ->lockForUpdate()
            ->where('id', $asset->id)
            ->update($update) !== 1) {
            throw new UnknownError;
        }
        return $asset->fresh();
    }

    public function withdraw(
        $agency,
        $currency,
        string $amount
    ) {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        if ($this
            ->getQuery($agency, $currency)
            ->update([
                'balance' => DB::raw("balance - $amount"),
            ]) !== 1) {
            throw new UnknownError;
        }
    }

    public function withdrawByAsset(asset $asset, string $amount)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        if ($this->asset
            ->lockForUpdate()
            ->where('id', $asset->id)
            ->update([
                'balance' => DB::raw("balance - $amount"),
            ]) !== 1) {
            throw new UnknownError;
        }
        return $asset->fresh();
    }
}
