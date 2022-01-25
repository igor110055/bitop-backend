<?php

namespace App\Repos\DB;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\{
    Deposit,
    User,
};

class DepositRepo implements \App\Repos\Interfaces\DepositRepo
{
    protected $group;

    public function __construct(Deposit $deposit) {
        $this->deposit = $deposit;
    }

    public function find($id)
    {
        return $this->deposit->find($id);
    }

    public function findOrFail($id)
    {
        return $this->deposit->findOrFail($id);
    }

    public function findByWalletId($wallet_id)
    {
        return $this->deposit
            ->where('wallet_id', $wallet_id)
            ->first();
    }

    public function create(array $values)
    {
        return $this->deposit->create($values);
    }

    public function queryDeposit($where = [], $keyword = null)
    {
        return $this->deposit
            ->when($where, function($query, $where){
                return $query->where($where);
            })
            ->when(($keyword and is_string($keyword)), function($query) use ($keyword) {
                return $query->where(function ($query) use ($keyword) {
                    $like = "%{$keyword}%";
                    return $query
                        ->orWhere('id', 'like', $like)
                        ->orWhere('user_id', 'like', $like);
                });
            })
            ->with(['user'])
            ->orderBy('id', 'desc');
    }

    public function countAll()
    {
        return $this->deposit->count();
    }

    public function getUserDeposits(
        User $user,
        $coin = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    ) {
        $query = $user->deposits()
            ->when($coin, function ($query, $coin) {
                return $query->where('coin', $coin);
            })
            ->whereNotNull('confirmed_at')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to);

        $total = $query->count();
        $data = $query
            ->latest()
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'filtered' => $data->count(),
            'data' => $data,
        ];
    }
}
