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
        parent::__construct();
        $this->TransactionRepo = $tr;
        $this->tz = config('core.timezone.default');
        $this->dateFormat = 'Y-m-d';
    }

    public function index()
    {
        $coins = array_merge(['All'], array_keys(config('coin')));
        $coins = array_combine($coins, $coins);
        $types = array_merge(['All' => 'All'], __('messages.transaction.types'));
        return view('admin.transactions', [
            'from' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
            'coins' => $coins,
            'types' => $types,
        ]);
    }

    public function search(SearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $coin = data_get($values, 'coin');
        $type = data_get($values, 'type');
        $sorting = null;

        $sort_map = [
            0 => 'id',
            1 => 'id',
            2 => 'account_id',
            3 => 'coin',
            4 => 'type',
            5 => 'amount',
            6 => 'balance',
            7 => 'remaining_amount',
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
        if ($type !== 'All') {
            $condition[] = ['type', '=', $type];
            \Log::debug($type);
        }
        $query = $this->TransactionRepo
            ->queryTransaction($condition, $keyword, true, true, $sorting);
        $total = $this->TransactionRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->balance = formatted_coin_amount($item->balance);
                $item->amount = formatted_coin_amount($item->amount);
                $item->user_text = data_get($item, 'account.user.username').' ('.data_get($item, 'account.user.id').')';
                switch ($item->type) {
                case Transaction::TYPE_TRANSFER_IN:
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'transactable.src_user_id')]);
                    $item->text = data_get($item, 'transactable.src_user.username');
                    break;
                case Transaction::TYPE_TRANSFER_OUT:
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'transactable.dst_user_id')]);
                    $item->text = data_get($item, 'transactable.dst_user.username');
                    break;
                case Transaction::TYPE_WALLET_DEPOSIT:
                    $item->link = route('admin.deposits.show', ['deposit' => data_get($item, 'transactable_id')]);
                    $item->text = data_get($item, 'transactable_id');;
                    break;
                case Transaction::TYPE_WALLET_WITHDRAWAL:
                    $item->link = route('admin.withdrawals.show', ['withdrawal' => data_get($item, 'transactable_id')]);
                    $item->text = data_get($item, 'transactable_id');;
                    break;
                case in_array($item->type, Transaction::ORDER_TYPES):
                    $item->link = route('admin.orders.show', ['order' => data_get($item, 'transactable_id')]);
                    $item->text = data_get($item, 'transactable_id');
                    break;
                case in_array($item->type, Transaction::MANUAL_TYPES):
                    $item->link = route('admin.users.show', ['user' => data_get($item, 'transactable.user_id')]);
                    $item->text = data_get($item, 'transactable.user.username');
                    break;
                case in_array($item->type, Transaction::WALLET_TYPES):
                    $item->link = route('admin.accounts.show', ['user' => data_get($item, 'transactable.user_id'), 'account' => data_get($item, 'account.id')]);
                    $item->text = data_get($item, 'transactable_id');
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
