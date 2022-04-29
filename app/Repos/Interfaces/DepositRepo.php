<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;
use App\Models\{
    Deposit,
    User,
};

interface DepositRepo
{
    public function find($id);
    public function findOrFail($id);
    public function findByWalletId($wallet_id);
    public function create(array $values);
    public function queryDeposit($where = [], $keyword = null, $sorting = null);
    public function countAll();
    public function getUserDeposits(
        User $user,
        $coin = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    );
}
