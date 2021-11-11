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
        return view('admin.withdrawals', [
            'from' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
        ]);
    }

    public function show(Withdrawal $withdrawal)
    {
        return view('admin.withdrawal', [
            'withdrawal' => $withdrawal,
        ]);
    }

    public function search(SearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $condition = $this->timeIntervalCondition('created_at', $from, $to);
        $query = $this->WithdrawalRepo
            ->queryWithdrawal($condition, $keyword);
        $total = $this->WithdrawalRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->username = $item->user->username;
                $item->time = Carbon::parse($item->created_at)->toDateTimeString();
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
