<?php

namespace App\Repos\DB;

use Dec\Dec;
use DB;
use Illuminate\Support\Facades\Log;

use App\Exceptions\{
    Account\InsufficientBalanceError,
    Account\InsufficientLockedBalanceException,
    Core\BadRequestError,
    Core\UnknownError,
};

use App\Models\{
    User,
    Account,
};

class AccountRepo implements \App\Repos\Interfaces\AccountRepo
{
    protected $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->coins = config('coin');
    }

    public function find(string $id)
    {
        return $this->account->find($id);
    }

    public function findForUpdate(string $id)
    {
        return $this->account
            ->lockForUpdate()
            ->find($id);
    }

    public function findOrFail(string $id)
    {
        return $this->account->findOrFail($id);
    }

    public function create($user, string $coin)
    {
        assert(in_array($coin, array_keys($this->coins)));
        $account = $this->account->create([
                'user_id' => data_get($user, 'id', $user),
                'coin' => $coin,
            ]);
        return $account->fresh();
    }

    public function getBalancesSum(string $coin)
    {
        return $this->account
            ->where('coin', $coin)
            ->sum('balance');
    }

    public function all()
    {
        return $this->account->all();
    }

    public function allByCoin(string $coin)
    {
        return $this->account
            ->where('coin', $coin)
            ->get();
    }

    public function allByUser($user)
    {
        return $this->account
            ->where('user_id', data_get($user, 'id', $user))
            ->get();
    }

    public function allByUserOrCreate($user)
    {
        $accounts = collect([]);

        $coins = array_keys(hide_beta_coins($user, $this->coins));
        foreach ($coins as $coin) {
            $account = $this->findByUserCoinOrCreate($user, $coin);
            $accounts->push($account);
        }
        return $accounts;
    }

    protected function getQuery($user, string $coin)
    {
        return $this->account
            ->where('user_id', data_get($user, 'id', $user))
            ->where('coin', $coin);
    }

    public function findByUserCoin($user, string $coin)
    {
        return $this->getQuery($user, $coin)->first();
    }

    public function findForUpdateByUserCoin($user, string $coin)
    {
        return $this->getQuery($user, $coin)
            ->lockForUpdate()
            ->first();
    }

    public function findByUserCoinOrFail($user, string $coin)
    {
        return $this->getQuery($user, $coin)->firstOrFail();
    }

    public function findByUserCoinOrCreate($user, string $coin)
    {
        if ($account = $this->findByUserCoin($user, $coin)) {
            return $account;
        }
        try {
            return $this->create($user, $coin)->fresh();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function lockByAccount(Account $account, string $amount)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        if ($this->account
            ->where('id', $account->id)
            ->whereRaw("locked_balance + $amount <= balance")
            ->update([
                'locked_balance' => DB::raw("locked_balance + $amount"),
            ]) !== 1) {
            throw new InsufficientBalanceError;
        }
        return $account->fresh();
    }

    public function lock($user, string $coin, string $amount)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        if ($this
            ->getQuery($user, $coin)
            ->whereRaw("locked_balance + $amount <= balance")
            ->update([
                'locked_balance' => DB::raw("locked_balance + $amount"),
            ]) !== 1) {
            throw new InsufficientBalanceError;
        }
    }

    public function unlockByAccount(Account $account, string $amount)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        if ($this->account
            ->where('id', $account->id)
            ->where('locked_balance', '>=', $amount)
            ->update([
                'locked_balance' => DB::raw("locked_balance - $amount"),
            ]) !== 1) {
            throw new InsufficientLockedBalanceException;
        }
        return $account->fresh();
    }

    public function unlock($user, string $coin, string $amount)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        if ($this
            ->getQuery($user, $coin)
            ->where('locked_balance', '>=', $amount)
            ->update([
                'locked_balance' => DB::raw("locked_balance - $amount"),
            ]) !== 1) {
            throw new InsufficientLockedBalanceException;
        }
    }

    public function depositByAccount(Account $account, string $amount, $unit_price = null)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }
        $update = ['balance' => DB::raw("balance + $amount")];

        if (isset($unit_price)) {
            if (Dec::create($unit_price)->isNegative()) {
                throw new BadRequestError('unit_price must be non-negative');
            }
            $scale = config('core.currency.rate_scale');
            if (is_null($account->unit_price)) {
                $update['unit_price'] = (string)Dec::create($unit_price, $scale);
            } else {
                $update['unit_price'] = (string)Dec::mul($account->balance, $account->unit_price)
                    ->add(Dec::mul($amount, $unit_price))
                    ->div(Dec::add($account->balance, $amount), $scale);
            }
        }

        if ($this->account
            ->where('id', $account->id)
            ->update($update) !== 1) {
            throw new UnknownError;
        }
        return $account->fresh();
    }

    public function deposit($user, string $coin, string $amount, $unit_price = null)
    {
        $account = $this->findByUserCoinOrCreate($user, $coin);
        return $this->depositByAccount($account, $amount, $unit_price);
    }

    public function withdrawByAccount(Account $account, string $amount)
    {
        if (!Dec::create($amount)->isPositive()) {
            throw new BadRequestError('Amount must be larger than 0.');
        }

        if ($this->account
            ->where('id', $account->id)
            ->whereRaw("balance - locked_balance >= $amount") # available-balance is sufficient
            ->update([
                'balance' => DB::raw("balance - $amount"),
            ]) !== 1) {
                throw new InsufficientBalanceError;
        }
        return $account->fresh();
    }

    public function withdraw(
        $user,
        $coin,
        string $amount
    ) {
        if ($this
            ->getQuery($user, $coin)
            ->whereRaw("balance - locked_balance >= $amount") # available-balance is sufficient
            ->update([
                'balance' => DB::raw("balance - $amount"),
            ]) !== 1) {
            throw new InsufficientBalanceError;
        }
    }

    public function assignAddrTag($user, string $coin, string $address, string $tag = null)
    {
        $account = $this->findByUserCoinOrCreate($user, $coin);
        return $account->update([
            'address' => $address,
            'tag' => $tag,
        ]);
    }
}
