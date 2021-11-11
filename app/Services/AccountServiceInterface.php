<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\{
    User,
    Account,
    Withdrawal,
};

interface AccountServiceInterface
{
    public function lock(
        $user,
        string $coin,
        string $amount,
        string $type,
        $transactable = null
    );

    public function unlock(
        $user,
        string $coin,
        string $amount,
        string $type,
        $transactable = null
    );

    public function deposit(
        $user,
        string $coin,
        string $amount,
        string $type,
        $unit_price = null,
        $transactable = null,
        string $message = null
    );

    public function withdraw(
        $user,
        string $coin,
        string $amount,
        string $type,
        $transactable = null,
        string $message = null
    );

    public function manipulate(
        Account $account,
        User $operator,
        string $type,
        $amount,
        $unit_price = null,
        string $note = null,
        string $message = null
    );

    public function calcWithdrawal(
        User $user,
        string $coin,
        string $amount,
        $throw_expcetions = false
    );

    public function createWithdrawal(
        User $user,
        string $coin,
        string $amount,
        string $address,
        string $tag = null,
        string $message = null
    );
    public function submitWithdrawal(Withdrawal $withdrawal);
    public function compareWithdrawals(Withdrawal $withdrawal, array $compared, $is_callback = false);
    public function cancelWithdrawal(Withdrawal $withdrawal, string $reason);
    public function handleWithdrawalCallback(Withdrawal $withdrawal, array $values);
    public function createDeposit(User $user, array $values);
    public function getWalletAddress(User $user, string $coin);
    public function updateUserDepositCallbacks(User $user);
    public function calculateWithdrawalValue(User $user, Carbon $from, Carbon $to);
}
