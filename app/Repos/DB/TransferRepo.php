<?php

namespace App\Repos\DB;

use DateTimeInterface;
use Carbon\Carbon;
use App\Exceptions\{
    TransferStatusError,
};
use App\Models\{
    Transfer,
    User,
};

class TransferRepo implements \App\Repos\Interfaces\TransferRepo
{
    protected $transfer;

    public function __construct(Transfer $transfer) {
        $this->transfer = $transfer;
    }

    public function find($id)
    {
        return $this->transfer->find($id);
    }

    public function findOrFail($id)
    {
        return $this->transfer->findOrFail($id);
    }

    public function create(array $values)
    {
        $transfer = $this->transfer->create($values);
        return $transfer->fresh();
    }

    public function queryTransfer($where = [])
    {
        return $this->transfer
            ->when($where, function($query, $where){
                return $query->where($where);
            });
    }

    public function cancel(Transfer $transfer)
    {
        if ($this->transfer
            ->where('id', $transfer->id)
            ->whereNull('canceled_at')
            ->whereNull('confirmed_at')
            ->update([
                'canceled_at' => millitime(),
            ]) !== 1) {
            throw new TransferStatusError;
        }
    }

    public function confirm(Transfer $transfer)
    {
        if ($this->transfer
            ->where('id', $transfer->id)
            ->whereNull('canceled_at')
            ->whereNull('confirmed_at')
            ->update([
                'confirmed_at' => millitime(),
            ]) !== 1) {
            throw new TransferStatusError;
        }
    }

    public function getExpiredTransfers()
    {
        return $this->transfer
            ->whereNull('canceled_at')
            ->whereNull('confirmed_at')
            ->where('expired_at', '<', millitime())
            ->get();
    }


    public function getTransaction(Transfer $transfer, string $type)
    {
        return $transfer->transactions()
            ->where('type', $type)
            ->latest()
            ->first();
    }

    public function getUserTransfers(
        User $user,
        string $coin = null,
        string $side,
        $search_user = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    ) {

        $query = $this->transfer->where("{$side}_user_id", $user->id);

        if ($search_user) {
            if ($side === 'dst') {
                $query = $query->where('src_user_id', $search_user->id);
            } elseif ($side === 'src') {
                $query = $query->where('dst_user_id', $search_user->id);
            }
        }
        
        $query = $query
            ->when($coin, function ($query, $coin) {
                return $query->where('coin', $coin);
            })
            ->whereNotNull('confirmed_at')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to);
        
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
