<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\SearchRequest;
use App\Repos\Interfaces\{
    WalletBalanceLogRepo,
};
use App\Models\WalletBalanceLog;

class WalletBalanceController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(WalletBalanceLogRepo $WalletBalanceLogRepo)
    {
        $this->WalletBalanceLogRepo = $WalletBalanceLogRepo;
        $this->tz = config('core.timezone.default');
        $this->dateFormat = 'Y-m-d';
    }

    public function getTransactions()
    {
        return view('admin.wallet_balance_transactions', [
            'from' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
        ]);
    }

    public function transactionSearch(SearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $condition = $this->timeIntervalCondition('created_at', $from, $to);
        $query = $this->WalletBalanceLogRepo
            ->queryLog($condition, $keyword, true);
        $total = $this->WalletBalanceLogRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->time = Carbon::parse($item->created_at)->toDateTimeString();
                $item->balance = formatted_coin_amount($item->balance, $item->coin);
                $item->amount = formatted_coin_amount($item->amount, $item->coin);
                switch ($item->type) {
                case WalletBalanceLog::TYPE_DEPOSIT:
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'wlogable.user.id')]);
                    $item->text = data_get($item, 'wlogable.user.username');
                    break;
                case WalletBalanceLog::TYPE_WITHDRAWAL:
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'wlogable.user.id')]);
                    $item->text = data_get($item, 'wlogable.user.username');
                    break;
                case WalletBalanceLog::TYPE_MANUAL_CORRECTION:
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'wlogable.user.id')]);
                    $item->text = data_get($item, 'wlogable.user.username');
                    break;
                }
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
