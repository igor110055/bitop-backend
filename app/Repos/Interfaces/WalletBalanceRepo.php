<?php

namespace App\Repos\Interfaces;

use App\Models\WalletBalance;

interface WalletBalanceRepo
{
    public function find(string $id);
    public function findOrFail(string $id);
    public function findByCoin(string $coin);
    public function findForUpdateByCoin(string $coin);
    public function create(array $values);
    public function getBalance(string $coin);
    public function deposit(WalletBalance $balance, string $amount);
    public function withdraw(WalletBalance $balance, string $amount);
}
