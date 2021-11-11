<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;
use App\Models\{
    WalletBalance,
};

interface WalletBalanceLogRepo
{
    public function getLogsByCoin(
        $coin,
        DateTimeInterface $from = null,
        DateTimeInterface $to = null,
        int $limit = 50,
        int $offset = 0
    );
    public function create(
        $wlogable,
        WalletBalance $wallet_balance,
        string $type,
        string $amount
    );
}
