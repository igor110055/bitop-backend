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
        $coins = array_merge(['All'], array_keys(config('coin')));
        $coins = array_combine($coins, $coins);
        return view('admin.wallet_balance_transactions', [
            'from' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'coins' => $coins,
        ]);
    }

    public function transactionSearch(SearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $coin = data_get($values, 'coin');

        $condition = $this->timeIntervalCondition('created_at', $from, $to);
        if ($coin !== 'All') {
            $condition[] = ['coin', '=', $coin];
        }
        $query = $this->WalletBalanceLogRepo
            ->queryLog($condition, $keyword, true);
        $total = $this->WalletBalanceLogRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->time = datetime($item->created_at);
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
