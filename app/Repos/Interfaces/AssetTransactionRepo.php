<?php

namespace App\Repos\Interfaces;

use App\Models\AssetTransaction;

interface AssetTransactionRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create($asset, $type, $amount, $balance, $unit_price = null, $result_unit_price = null, $transactable = null);
    public function getFilteringQuery($asset = null, $from = null, $to = null, $keyword = null, $with_transactable = false);
}
