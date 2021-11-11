<?php

namespace App\Services;

use Dec\Dec;
use Illuminate\Support\Facades\Log;
use DB;

use App\Models\{
    AssetTransaction,
};
use App\Repos\Interfaces\{
    AssetRepo,
    AssetTransactionRepo,
    ManipulationRepo,
};
use App\Exceptions\Core\BadRequestError;

class AssetService implements  AssetServiceInterface
{
    public function __construct(
        AssetRepo $AssetRepo,
        AssetTransactionRepo $AssetTransactionRepo,
        ManipulationRepo $ManipulationRepo
    ) {
        $this->AssetRepo = $AssetRepo;
        $this->AssetTransactionRepo = $AssetTransactionRepo;
        $this->ManipulationRepo = $ManipulationRepo;
        $this->currencies = config('core.currency.all');
    }
    public function deposit(
        $agency,
        $currency,
        string $amount,
        $type,
        $unit_price = null,
        $transactable = null
    ) {
        assert(in_array($currency, $this->currencies));

        $asset = $this->AssetRepo
            ->findByAgencyCurrencyOrCreate(
                data_get($agency, 'id', $agency),
                $currency
            );

        return DB::transaction(function () use ($agency, $asset, $amount, $type, $unit_price, $transactable) {
            $asset = $this->AssetRepo
                ->depositByAsset(
                    $asset,
                    $amount,
                    $unit_price
                );
            $transaction = $this->AssetTransactionRepo
                ->create(
                    $asset,
                    $type,
                    $amount,
                    $asset->balance,
                    $unit_price,
                    $asset->unit_price,
                    $transactable
                );
            return $asset;
        });
    }

    public function withdraw(
        $agency,
        $currency,
        string $amount,
        $type,
        $transactable = null
    ) {
        assert(in_array($currency, $this->currencies));

        $asset = $this->AssetRepo
            ->findByAgencyCurrencyOrCreate(
                data_get($agency, 'id', $agency),
                $currency
            );

        return DB::transaction(function () use ($agency, $asset, $amount, $type, $transactable) {
            $asset = $this->AssetRepo
                ->withdrawByAsset(
                    $asset,
                    $amount
                );
            $transaction = $this->AssetTransactionRepo
                ->create(
                    $asset,
                    $type,
                    (string)Dec::create($amount)->additiveInverse(),
                    $asset->balance,
                    null,                                               # unit_price
                    $asset->unit_price,
                    $transactable
                );
            return $asset;
        });
    }

    public function manipulate(
        $asset,
        $user,
        $type,
        $amount,
        $unit_price,
        $note
    ) {
        assert(in_array($type, [
            AssetTransaction::TYPE_MANUAL_DEPOSIT,
            AssetTransaction::TYPE_MANUAL_WITHDRAWAL,
        ]));

        $scale = config('core.currency.scale');
        $rate_scale = config('core.currency.rate_scale');
        $amount = (string)Dec::create($amount)->floor($scale);
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('amount shoule be positive');
        }
        if (!is_null($unit_price)) {
            $unit_price = (string)Dec::create($unit_price)->floor($rate_scale);
        }

        DB::transaction(function () use ($asset, $user, $type, $amount, $unit_price, $note) {
            $manipulation = $this->ManipulationRepo
                ->create(
                    $user,
                    $note
                );

            $agency = $asset->agency;

            if ($type === AssetTransaction::TYPE_MANUAL_DEPOSIT) {
                # deposit to asset
                $this->deposit(
                    $agency,
                    $asset->currency,
                    $amount,
                    AssetTransaction::TYPE_MANUAL_DEPOSIT,
                    $unit_price,
                    $manipulation
                );
            } elseif ($type === AssetTransaction::TYPE_MANUAL_WITHDRAWAL) {
                # withdraw from asset
                $this->withdraw(
                    $agency,
                    $asset->currency,
                    $amount,
                    AssetTransaction::TYPE_MANUAL_WITHDRAWAL,
                    $manipulation
                );
            }
        });
    }
}
