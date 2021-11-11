<?php

namespace App\Repos\Interfaces;

use App\Models\{
    User,
    Account,
};

interface AccountRepo
{
    public function find(string $id);
    public function findForUpdate(string $id);
    public function findOrFail(string $id);
    public function create($user, string $coin);
    public function getBalancesSum(string $coin);
    public function all();
    public function allByCoin(string $coin);
    public function allByUser($user);
    public function allByUserOrCreate($user);
    public function findByUserCoin($user, string $coin);
    public function findForUpdateByUserCoin($user, string $coin);
    public function findByUserCoinOrFail($user, string $coin);
    public function findByUserCoinOrCreate($user, string $coin);
    public function lockByAccount(Account $account, string $amount);
    public function lock($user, string $coin, string $amount);
    public function unlockByAccount(Account $account, string $amount);
    public function unlock($user, string $coin, string $amount);
    public function depositByAccount(Account $account, string $amount, $unit_price = null);
    public function deposit($user, string $coin, string $amount, $unit_price = null);
    public function withdrawByAccount(Account $account, string $amount);
    public function withdraw($user, string $coin, string $amount);
    public function assignAddrTag($user, string $coin, string $address, string $tag = null);
}
