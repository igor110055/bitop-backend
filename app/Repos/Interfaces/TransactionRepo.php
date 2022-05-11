<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;
use App\Models\{
    User,
    Account,
};

interface TransactionRepo
{
    public function find($id);
    public function findOrFail($id);
    public function getUserTransactions(
        User $user,
        $coin,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    );
    public function countAllByAccount(Account $account);
    public function countAll();
    public function create($account, $coin, $type, $amount, $balance, $unit_price, $result_unit_price, $is_locked = false, $transactable = null, $status = true, $message = null);
    public function queryTransaction($where = [], $keyword = null, $with_transactable = false, $with_user = false);
}
