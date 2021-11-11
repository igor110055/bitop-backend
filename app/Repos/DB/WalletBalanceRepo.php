<?php

namespace App\Repos\DB;

use DB;
use Dec\Dec;
use App\Exceptions\{
    WalletBalanceInsufficientError,
};
use App\Models\WalletBalance;

class WalletBalanceRepo implements \App\Repos\Interfaces\WalletBalanceRepo
{
    public function __construct(WalletBalance $balance)
    {
        $this->balance = $balance;
    }

    public function find(string $id)
    {
        return $this->balance->find($id);
    }

    public function findOrFail(string $id)
    {
        return $this->balance->findOrFail($id);
    }

    public function findByCoin(string $coin)
    {
        return $this->balance
            ->where('coin', $coin)
            ->first();
    }

    public function findForUpdateByCoin(string $coin)
    {
        return $this->balance
            ->where('coin', $coin)
            ->lockForUpdate()
            ->first();
    }

    public function create(array $values)
    {
        return $this->balance->create($values);
    }

    public function getBalance(string $coin)
    {
        return optional(
            $this->balance
            ->where('coin', $coin)
            ->first())->balance;
    }

    public function deposit(WalletBalance $balance, string $amount)
    {
        if ($this->balance
            ->where('id', $balance->id)
            ->update(['balance' => DB::raw("balance + $amount")]) !== 1
        ) {
            throw new \Exception;
        }
    }

    public function withdraw(WalletBalance $balance, string $amount)
    {
        if ($this->balance
            ->where('id', $balance->id)
            ->whereRaw("balance >= $amount")
            ->update(['balance' => DB::raw("balance - $amount")]) !== 1
        ) {
            \Log::alert("Wallet total balance insufficient", ['balance' => $balance->balance, 'amount' => $amount]);
            throw new WalletBalanceInsufficientError;
        }
    }
}
