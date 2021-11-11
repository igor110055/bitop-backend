<?php

namespace App\Repos\Interfaces;

interface WalletLogRepo
{
    public function findByWalletIdType(string $wallet_id, string $type);
    public function create(array $values);
}
