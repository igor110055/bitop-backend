<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Repos\Interfaces\TransactionRepo;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\SearchRequest;
use App\Models\{
    Transaction,
};

class TransactionController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(TransactionRepo $tr)
    {
        $this->TransactionRepo = $tr;
        $this->tz = config('core.timezone.default');
        $this->dateFormat = 'Y-m-d';
    }

    public function index()
    {
        $coins = array_merge(['All'], array_keys(config('coin')));
        $coins = array_combine($coins, $coins);
        return view('admin.transactions', [
            'from' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'coins' => $coins,
        ]);
    }

    public function search(SearchRequest $request)
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
        $query = $this->TransactionRepo
            ->queryTransaction($condition, $keyword, true);
        $total = $this->TransactionRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->balance = formatted_coin_amount($item->balance);
                $item->amount = formatted_coin_amount($item->amount);
                switch ($item->type) {
                case Transaction::TYPE_TRANSFER_IN:
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'transactable.src_user.id')]);
                    $item->text = data_get($item, 'transactable.src_user.username');
                    break;
                case Transaction::TYPE_TRANSFER_OUT:
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'transactable.dst_user.id')]);
                    $item->text = data_get($item, 'transactable.dst_user.username');
                    break;
                case in_array($item->type, Transaction::ORDER_TYPES):
                    $item->link = route('admin.orders.show', ['order' => data_get($item, 'transactable.id')]);
                    $item->text = data_get($item, 'transactable.id');
                    break;
                case in_array($item->type, Transaction::MANUAL_TYPES):
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'transactable.user.id')]);
                    $item->text = data_get($item, 'transactable.user.name');
                    break;
                case in_array($item->type, Transaction::WALLET_TYPES):
                    $item->link = route('admin.accounts.show', ['user' => data_get($item, 'transactable.user.id'), 'account' => data_get($item, 'account.id')]);
                    $item->text = data_get($item, 'transactable.user.name');
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
