<?php

namespace App\Repos\DB;

use DateTimeInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\{
    Transaction,
    Withdrawal,
    User,
};
use App\Exceptions\{
    DuplicateRecordError,
    WithdrawalStatusError,
};

class WithdrawalRepo implements \App\Repos\Interfaces\WithdrawalRepo
{
    public function __construct(Withdrawal $withdrawal) {
        $this->withdrawal = $withdrawal;
    }

    public function find($id)
    {
        return $this->withdrawal->find($id);
    }

    public function findOrFail($id)
    {
        return $this->withdrawal->findOrFail($id);
    }

    public function findByWalletId($wallet_id)
    {
        return $this->withdrawal
            ->where('wallet_id', $wallet_id)
            ->first();
    }

    public function update(Withdrawal $withdrawal, array $values)
    {
        return $withdrawal->update($values);
    }

    public function updateMetadata(Withdrawal $withdrawal, array $values)
    {
        $data = [
            'wallet_id' => data_get($values, 'id'),
            'type' => data_get($values, 'type'),
            'transaction' => data_get($values, 'transaction'),
            'src_amount' => data_get($values, 'src_amount'),
            'dst_amount' => data_get($values, 'dst_amount'),
            'wallet_fee' => is_null(data_get($values, 'fee'))? '0' : data_get($values, 'fee'),
            'wallet_fee_coin' => data_get($values, 'fee_currency') ?? data_get($values, 'currency'),
            'response' => $values,
        ];
        return $this->update($withdrawal, $data);
    }

    public function create(array $values)
    {
        return $this->withdrawal->create($values);
    }

    public function findMainTransaction(Withdrawal $withdrawal)
    {
        return $withdrawal
            ->transactions()
            ->where('type', Transaction::TYPE_WALLET_WITHDRAWAL)
            ->first();
    }

    public function getUserLatest(User $user)
    {
        return $user->withdrawals()
            ->whereNull('canceled_at')
            ->latest()
            ->first();
    }

    public function getAllUnconfirmedExpired()
    {
        return $this->withdrawal
            ->whereNull('submitted_confirmed_at')
            ->whereNull('submitted_at')
            ->whereNull('confirmed_at')
            ->whereNull('canceled_at')
            ->where('expired_at', '<', millitime())
            ->get();
    }

    public function getAllPending()
    {
        return $this->withdrawal
            ->whereNull('submitted_confirmed_at')
            ->whereNotNull('confirmed_at')
            ->whereNull('canceled_at')
            ->get();
    }

    public function setSubmittedConfirmed(Withdrawal $withdrawal)
    {
        if ($this->withdrawal
            ->lockForUpdate()
            ->where('id', $withdrawal->id)
            ->whereNull('submitted_confirmed_at')
            ->whereNotNull('confirmed_at')
            ->update([
                'submitted_confirmed_at' => millitime(),
            ]) !== 1) {
            throw new WithdrawalStatusError;
        }
    }

    public function setSubmitted(Withdrawal $withdrawal)
    {
        if ($this->withdrawal
            ->lockForUpdate()
            ->where('id', $withdrawal->id)
            ->whereNull('submitted_confirmed_at')
            ->whereNull('canceled_at')
            ->whereNotNull('confirmed_at')
            ->update([
                'submitted_at' => millitime(),
            ]) !== 1) {
            throw new WithdrawalStatusError;
        }
    }

    public function cancel(Withdrawal $withdrawal)
    {
        if ($this->withdrawal
            ->lockForUpdate()
            ->where('id', $withdrawal->id)
            ->whereNull('canceled_at')
            ->whereNull('submitted_confirmed_at')
            ->update([
                'canceled_at' => millitime(),
            ]) !== 1) {
            throw new WithdrawalStatusError;
        }
    }

    public function confirm(Withdrawal $withdrawal)
    {
        if ($this->withdrawal
            ->lockForUpdate()
            ->where('id', $withdrawal->id)
            ->whereNull('confirmed_at')
            ->whereNull('canceled_at')
            ->whereNull('submitted_confirmed_at')
            ->update([
                'confirmed_at' => millitime(),
            ]) !== 1) {
            throw new WithdrawalStatusError;
        }
    }

    public function setNotifed(Withdrawal $withdrawal)
    {
        if ($this->withdrawal
            ->lockForUpdate()
            ->where('id', $withdrawal->id)
            ->whereNull("notified_at")
            ->update([
                'notified_at' => millitime(),
            ]) !== 1) {
            throw new DuplicateRecordError;
        }
    }

    public function queryWithdrawal($where = [], $keyword = null)
    {
        return $this->withdrawal
            ->when($where, function($query, $where){
                return $query->where($where);
            })
            ->when(($keyword and is_string($keyword)), function($query) use ($keyword) {
                return $query->where(function ($query) use ($keyword) {
                    $like = "%{$keyword}%";
                    return $query
                        ->orWhere('id', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('user_id', 'like', $like);
                });
            })
            ->with('user')
            ->orderBy('id', 'desc');
    }

    public function countAll()
    {
        return $this->withdrawal->count();
    }

    public function getUserUncanceledWithdrawals(
        User $user,
        $coin = null,
        Carbon $from,
        Carbon $to
    ) {
        return $user->withdrawals()
            ->when($coin, function ($query, $coin) {
                return $query->where('coin', $coin);
            })
            ->whereNull('canceled_at')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->latest()
            ->get();
    }

    public function getUserWithdrawals(
        User $user,
        $coin = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    ) {
        $query = $user->withdrawals()
            ->when($coin, function ($query, $coin) {
                return $query->where('coin', $coin);
            })
            ->whereNotNull('confirmed_at')
            ->where('confirmed_at', '>=', $from)
            ->where('confirmed_at', '<', $to);

        $total = $query->count();
        $data = $query
            ->latest('confirmed_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'filtered' => $data->count(),
            'data' => $data,
        ];
    }

    public function getTransaction(Withdrawal $withdrawal, string $type)
    {
        return $withdrawal->transactions()
            ->where('type', $type)
            ->latest()
            ->first();
    }
}
