<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;
use Carbon\Carbon;
use App\Models\{
    Withdrawal,
    User,
};

interface WithdrawalRepo
{
    public function find($id);
    public function findOrFail($id);
    public function findByWalletId($wallet_id);
    public function update(Withdrawal $withdrawal, array $values);
    public function updateMetadata(Withdrawal $withdrawal, array $values);
    public function create(array $values);
    public function findMainTransaction(Withdrawal $withdrawal);
    public function getUserLatest(User $user);
    public function getAllUnconfirmedExpired();
    public function getAllPending();
    public function setSubmittedConfirmed(Withdrawal $withdrawal);
    public function setSubmitted(Withdrawal $withdrawal);
    public function cancel(Withdrawal $withdrawal);
    public function confirm(Withdrawal $withdrawal);
    public function setNotifed(Withdrawal $withdrawal);
    public function queryWithdrawal($where = [], $keyword = null, $sorting = null);
    public function countAll();
    public function getUserUncanceledWithdrawals(
        User $user,
        $coin = null,
        Carbon $from,
        Carbon $to
    );
    public function getUserWithdrawals(
        User $user,
        $coin = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    );
    public function getTransaction(Withdrawal $withdrawal, string $type);
}
