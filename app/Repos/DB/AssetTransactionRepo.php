<?php

namespace App\Repos\DB;

use App\Models\{
    Agency,
    Asset,
    AssetTransaction,
};
use App\Repos\Interfaces\AssetRepo;

class AssetTransactionRepo implements \App\Repos\Interfaces\AssetTransactionRepo
{
    public function __construct(AssetTransaction $AssetTransaction, AssetRepo $AssetRepo) {
        $this->AssetTransaction = $AssetTransaction;
        $this->AssetRepo = $AssetRepo;
    }

    public function find($id)
    {
        return $this->AssetTransaction->find($id);
    }

    public function findOrFail($id)
    {
        return $this->AssetTransaction->findOrFail($id);
    }

    public function getAllByAsset(Agency $agency, $currency)
    {
        $asset = $this->AssetRepo->findByAssetCurrencyOrCreate($agency, $currency);

        return $asset->asset_transactions()
            ->get();
    }

    public function create(
        $asset,
        $type,
        $amount,
        $balance,
        $unit_price = null,
        $result_unit_price = null,
        $transactable = null
    ) {
        assert(in_array($type, AssetTransaction::TYPES)); # make sure that type is valid

        $data = [
            'asset_id' => data_get($asset, 'id', $asset),
            'type' => $type,
            'amount' => $amount,
            'balance' => $balance,
            'unit_price' => $unit_price,
            'result_unit_price' => $result_unit_price,
        ];

        if ($transactable) {
            $transaction = $transactable->asset_transactions()->create($data);
        } else {
            $transaction = $this->AssetTransaction->create($data);
        }
        return $transaction->fresh();
    }

    public function getFilteringQuery($asset = null, $from = null, $to = null, $keyword = null, $with_transactable = false)
    {
        $query = $this->AssetTransaction;
        if ($with_transactable) {
            $query = $query->with('transactable');
        }
        if ($asset) {
            $query = $query->where('asset_id', data_get($asset, 'id', $asset));
        }
        if ($from) {
            $query = $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query = $query->where('created_at', '<', $to);
        }
        if ($keyword and is_string($keyword)) {
            $query = $query->where(function ($query) use ($keyword) {
                $like = "%{$keyword}%";
                return $query
                    ->orWhere('transactable_id', 'like', $like);
            });
        }
        $query = $query->orderBy('id', 'desc');
        return $query;
    }

    public function getAssetTransactionsCount(Asset $asset)
    {
        return $asset->asset_transactions()->count();
    }
}
