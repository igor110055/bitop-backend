<?php

namespace App\Repos\DB;

use DateTimeInterface;
use App\Models\{
    WalletBalance,
    WalletBalanceLog,
};

class WalletBalanceLogRepo implements \App\Repos\Interfaces\WalletBalanceLogRepo
{
    protected $log;

    public function __construct(WalletBalanceLog $log, WalletBalanceRepo $wbr)
    {
        $this->log = $log;
        $this->WalletBalanceRepo = $wbr;
    }

    public function getLogsByCoin(
        $coin,
        DateTimeInterface $from = null,
        DateTimeInterface $to = null,
        int $limit = 50,
        int $offset = 0
    ) {
        $wallet_balance = $this->WalletBalanceRepo->findByCoin($coin);

        $query = $wallet_balance->wallet_balance_logs()->with('wlogable');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return [
            'total' => $query->count(),
            'filtered' => min($query->count(), $limit),
            'data' => $query
            ->latest('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get(),
        ];
    }

    public function create(
        $wlogable,
        WalletBalance $wallet_balance,
        string $type,
        string $amount
    ) {
        assert(in_array($type, WalletBalanceLog::TYPES));

        return $wlogable->wallet_balance_logs()->create([
            'wallet_balance_id' => $wallet_balance->id,
            'coin' => $wallet_balance->coin,
            'type' => $type,
            'amount' => $amount,
            'balance' => $wallet_balance->balance,
        ]);
    }

    public function countAll()
    {
        return $this->log->count();
    }

    public function queryLog($where = [], $keyword = null, $with_wlogable = false)
    {
        return $this->log
            ->when($where, function($query, $where){
                return $query->where($where);
            })
            ->when(($keyword and is_string($keyword)), function($query) use ($keyword) {
                return $query->where(function ($query) use ($keyword) {
                    $like = "%{$keyword}%";
                    return $query
                        ->orWhere('id', 'like', $like)
                        ->orWhere('wlogable_id', 'like', $like);
                });
            })
            ->when($with_wlogable, function($query) {
                return $query->with('wlogable');
            })
            ->orderBy('id', 'desc');
    }
}
