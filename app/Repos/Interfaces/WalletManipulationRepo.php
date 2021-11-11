<?php

namespace App\Repos\Interfaces;

use App\Models\{
    WalletManipulation,
};

interface WalletManipulationRepo
{
    public function findByWalletIdType(string $wallet_id, string $type);
    public function findByTransaction(string $transaction);
    public function create(array $values);
    public function updateCallbackResponse(WalletManipulation $manipulate, array $values);
}
