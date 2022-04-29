<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Repos\Interfaces\WithdrawalRepo;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\SearchRequest;
use App\Models\Withdrawal;

class WithdrawalController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(WithdrawalRepo $wr)
    {
        $this->WithdrawalRepo = $wr;
        $this->tz = config('core.timezone.default');
        $this->dateFormat = 'Y-m-d';
    }

    public function index()
    {
        $coins = array_merge(['All'], array_keys(config('coin')));
        $coins = array_combine($coins, $coins);
        return view('admin.withdrawals', [
            'from' => Carbon::parse('today - 3 months', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'coins' => $coins,
        ]);
    }

    public function show(Withdrawal $withdrawal)
    {
        return view('admin.withdrawal', [
            'withdrawal' => $withdrawal,
            'user' => $withdrawal->user,
        ]);
    }

    public function search(SearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today - 3 months'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $coin = data_get($values, 'coin');
        $sorting = null;

        $sort_map = [
            0 => 'id',
            1 => 'user_id',
            2 => 'created_at',
            3 => 'notified_at',
            4 => 'coin',
            5 => 'amount',
            6 => 'fee',
        ];
        $column_key = data_get($values, 'order.0.column');
        if (array_key_exists($column_key, $sort_map)) {
            $sorting = [
                'column' => $sort_map[$column_key],
                'dir' => data_get($values, 'order.0.dir'),
            ];
        }


        $condition = $this->timeIntervalCondition('created_at', $from, $to);
        if ($coin !== 'All') {
            $condition[] = ['coin', '=', $coin];
        }

        $query = $this->WithdrawalRepo
            ->queryWithdrawal($condition, $keyword, $sorting);
        $total = $this->WithdrawalRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->username = $item->user->username;
                $item->amount = formatted_coin_amount($item->amount);
                $item->fee = formatted_coin_amount($item->fee);
                return $item;
            });
        return $this->draw(
            $this->result(
                $total,
                $filtered,
                $data
            )
        );
    }
}
