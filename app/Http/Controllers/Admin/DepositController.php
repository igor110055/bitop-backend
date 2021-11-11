<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Repos\Interfaces\DepositRepo;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\SearchRequest;
use App\Models\Deposit;

class DepositController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(DepositRepo $dr)
    {
        $this->DepositRepo = $dr;
        $this->tz = config('core.timezone.default');
        $this->dateFormat = 'Y-m-d';
    }

    public function index()
    {
        return view('admin.deposits', [
            'from' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
        ]);
    }

    public function show(Deposit $deposit)
    {
        return view('admin.deposit', [
            'deposit' => $deposit,
        ]);
    }

    public function search(SearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $condition = $this->timeIntervalCondition('created_at', $from, $to);
        $query = $this->DepositRepo
            ->queryDeposit($condition, $keyword);
        $total = $this->DepositRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->username = $item->user->username;
                $item->create_time = Carbon::parse($item->created_at)->toDateTimeString();
                $item->confirm_time  = Carbon::parse($item->confirmed_at)->toDateTimeString();
                $item->amount = formatted_coin_amount($item->amount);
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
