<?php

namespace App\Repos\DB;

use DB;
use DateTimeInterface;
use Carbon\Carbon;
use Dec\Dec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

use App\Exceptions\{
    Core\UnknownError,
};
use App\Models\{
    Order,
    User,
};

class OrderRepo implements \App\Repos\Interfaces\OrderRepo
{
    protected $order;

    public function __construct(Order $order) {
        $this->order = $order;
    }

    public function find($id)
    {
        return $this->order->find($id);
    }

    public function findOrFail($id)
    {
        return $this->order->findOrFail($id);
    }

    public function findForUpdate($id)
    {
        return $this->order
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function create($values)
    {
        $values = [
            'src_user_id' => data_get($values, 'src_user_id'),
            'dst_user_id' => data_get($values, 'dst_user_id'),
            'status' => ORDER::STATUS_PROCESSING,
            'coin' => data_get($values, 'coin'),
            'amount' => data_get($values, 'amount'),
            'fee' => data_get($values, 'fee'),
            'currency' => data_get($values, 'currency'),
            'total' => data_get($values, 'total'),
            'unit_price' => data_get($values, 'unit_price'),
            'fee_setting_id' => data_get($values, 'fee_setting_id'),
            'advertisement_id' => data_get($values, 'advertisement_id'),
            'expired_at' => data_get($values, 'expired_at'),
        ];
        $order = $this->order->create($values);
        return $order->fresh();
    }

    public function update($order, $values)
    {
        $values = Arr::only($values, [
            'profit',
            'coin_unit_price',
            'currency_unit_price',
            'status',
            'claimed_at',
            'revoked_at',
            'completed_at',
            'canceled_at',
        ]);

        if ($this
            ->order
            ->where('id', data_get($order, 'id', $order))
            ->update($values) !== 1) {
            throw new UnknownError;
        }
    }

    public function getUserOrders(
        $user,
        $status = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    ) {

        $query = $this->order
            ::with(['bank_accounts', 'bank_accounts.bank'])
            ->where(function ($query) use ($user) {
                $query->where('src_user_id', data_get($user, 'id', $user))
                      ->orWhere('dst_user_id', data_get($user, 'id', $user));
            })
            ->when($status, function($query, $status) {
                if ($status === Order::STATUS_PROCESSING) {
                    return $query->whereIn('status', [Order::STATUS_PROCESSING, Order::STATUS_CLAIMED]);
                } else {
                    return $query->where('status', $status);
                }
            })
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

    public function getUserSellOrders($user, $status = null)
    {
        return $this->order
            ->where('src_user_id', data_get($user, 'id', $user))
            ->when($status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->get();
    }

    public function getUserBuyOrdersCount($user, $status = null)
    {
        $request = $this->order
            ->where('dst_user_id', data_get($user, 'id', $user))
            ->when($status, function($query, $status) {
                return $query->where('status', $status);
            });
        return $request->count();
    }

    public function getUserSellOrdersCount($user, $status = null)
    {
        $request = $this->order
            ->where('src_user_id', data_get($user, 'id', $user))
            ->when($status, function($query, $status) {
                return $query->where('status', $status);
            });
        return $request->count();
    }

    public function getUserOrdersCount($user, $status = null)
    {
        $request = $this->order
            ->where(function ($query) use ($user) {
                $query->where('src_user_id', data_get($user, 'id', $user))
                      ->orWhere('dst_user_id', data_get($user, 'id', $user));
            })
            ->when($status, function($query, $status) {
                return $query->where('status', $status);
            });
        return $request->count();
    }

    public function getOrdersCount()
    {
        return $this->order->count();
    }

    public function getUserAveragePayTime($user)
    {
        $orders = $this->order
            ->where('dst_user_id', data_get($user, 'id', $user))
            ->whereNotNull('completed_at')
            ->get();
        if ($orders->isEmpty()) {
            return null;
        }
        $pay_times = $orders->map(function($item) {
                return Carbon::parse($item->created_at)
                    ->diffInSeconds(Carbon::parse($item->claimed_at));
            });
        $avg = Dec::create(0);
        foreach ($pay_times as $pay_time) {
            $avg = Dec::add($avg, $pay_time);
        }
        return (string)Dec::div($avg, $pay_times->count())->floor(0);
    }

    public function getUserAverageReleaseTime($user)
    {
        $orders = $this->order
            ->where('src_user_id', data_get($user, 'id', $user))
            ->whereNotNull('completed_at')
            ->get();
        if ($orders->isEmpty()) {
            return null;
        }
        $release_times = $orders->map(function($item) {
                return Carbon::parse($item->claimed_at)
                    ->diffInSeconds(Carbon::parse($item->completed_at));
            });
        $avg = Dec::create(0);
        foreach ($release_times as $release_time) {
            $avg = Dec::add($avg, $release_time);
        }
        return (string)Dec::div($avg, $release_times->count())->floor(0);
    }

    public function queryOrder($where = [], $keyword = null, $user = null)
    {
        $query = $this->order->with(['src_user', 'dst_user']);
        if ($user) {
            $query->where(function ($query) use ($user) {
                $query->where('src_user_id', data_get($user, 'id', $user))
                      ->orWhere('dst_user_id', data_get($user, 'id', $user));
            });
        }
        return $query
            ->when($where, function($query, $where){
                return $query->where($where);
            })
            ->when(($keyword and is_string($keyword)), function($query) use ($keyword) {
                return $query->where(function ($query) use ($keyword) {
                    $like = "%{$keyword}%";
                    $query->where('id', 'like', $like)
                        ->orWhereHas('src_user', function (Builder $query) use ($like) {
                            $query->where('username', 'like', $like);
                        })->orWhereHas('dst_user', function (Builder $query) use ($like) {
                            $query->where('username', 'like', $like);
                        });
                });

            })
            ->orderBy('id', 'desc');
    }
    public function getExpiredOrders()
    {
        return $this->order
            ->whereNull('canceled_at')
            ->whereNull('claimed_at')
            ->whereNull('completed_at')
            ->where('expired_at', '<', millitime())
            ->get();
    }
}
