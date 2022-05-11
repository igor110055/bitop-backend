<?php

namespace App\Repos\DB;

use DateTimeInterface;
use App\Models\{
    Transaction,
    User,
    Account,
};
use App\Repos\Interfaces\AccountRepo;

class TransactionRepo implements \App\Repos\Interfaces\TransactionRepo
{
    protected $account;

    public function __construct(Transaction $transaction, AccountRepo $ar) {
        $this->transaction = $transaction;
        $this->AccountRepo = $ar;
    }

    public function find($id)
    {
        return $this->transaction->find($id);
    }

    public function findOrFail($id)
    {
        return $this->transaction->findOrFail($id);
    }

    public function getUserTransactions(
        User $user,
        $coin,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    ) {
        $account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);

        $query = $account->transactions()
            ->with('transactable')
            ->where('is_locked', false)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to);

        $total = $query->count();
        $data = $query
            ->latest('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'filtered' => $data->count(),
            'data' => $data,
        ];
    }

    public function countAllByAccount(Account $account)
    {
        return $account->transactions()
            ->where('is_locked', false)
            ->count();
    }

    public function countAll()
    {
        return $this->transaction
            ->where('is_locked', false)
            ->count();
    }

    public function create(
        $account,
        $coin,
        $type,
        $amount,
        $balance,
        $unit_price,
        $result_unit_price,
        $is_locked = false,
        $transactable = null,
        $status = true,
        $message = null
    ) {
        assert(in_array($type, Transaction::TYPES)); # make sure that type is valid

        if ($transactable) {
            $transaction = $transactable->transactions()
                ->create([
                    'account_id' => data_get($account, 'id', $account),
                    'coin' => $coin,
                    'type' => $type,
                    'amount' => $amount,
                    'balance' => $balance,
                    'unit_price' => $unit_price,
                    'result_unit_price' => $result_unit_price,
                    'is_locked' => $is_locked ? true : false,
                    'message' => $message,
                ]);
        } else {
            $transaction = $this->transaction
                ->create([
                    'account_id' => data_get($account, 'id', $account),
                    'coin' => $coin,
                    'type' => $type,
                    'amount' => $amount,
                    'balance' => $balance,
                    'unit_price' => $unit_price,
                    'result_unit_price' => $result_unit_price,
                    'is_locked' => $is_locked ? true : false,
                    'message' => $message,
               ]);
        }
        return $transaction->fresh();
    }

    public function queryTransaction($where = [], $keyword = null, $with_transactable = false, $with_user = false)
    {
        return $this->transaction
            ->where('is_locked', false)
            ->when($where, function($query, $where){
                return $query->where($where);
            })
            ->when(($keyword and is_string($keyword)), function($query) use ($keyword) {
                return $query->where(function ($query) use ($keyword) {
                    $like = "%{$keyword}%";
                    return $query
                        ->orWhere('id', 'like', $like)
                        ->orWhere('transactable_id', 'like', $like);
                });
            })
            ->when($with_transactable, function($query) {
                return $query->with('transactable');
            })
            ->when($with_user, function($query) {
                return $query->with('account.user');
            })
            ->orderBy('id', 'desc');
    }
}
