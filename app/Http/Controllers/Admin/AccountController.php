<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
};
use App\Http\Requests\Admin\{
    AccountManipulationRequest,
};
use App\Models\{
    Account,
    Manipulation,
    Transaction,
    Transfer,
    User,
};
use App\Repos\Interfaces\{
    UserRepo,
    AccountRepo,
    TransactionRepo,
};
use App\Services\{
    AccountServiceInterface,
};

class AccountController extends AdminController
{
    use DataTableTrait;

    public function __construct(
        UserRepo $ur,
        AccountRepo $ar,
        TransactionRepo $tr,
        AccountServiceInterface $as
    ) {
        parent::__construct();
        $this->UserRepo = $ur;
        $this->AccountRepo = $ar;
        $this->TransactionRepo = $tr;
        $this->AccountService = $as;

        $this->middleware(
            ['role:super-admin'],
            ['only' => [
                'createManipulation',
                'storeManipulation',
            ]]
        );
    }

    public function show(Account $account)
    {
        return view('admin.account', [
            'user' => $account->user,
            'account' => $account,
        ]);
    }

    public function createManipulation(Account $account)
    {
        $user = $account->user;
        $types = [
            Transaction::TYPE_MANUAL_DEPOSIT => '手動充值',
            Transaction::TYPE_MANUAL_WITHDRAWAL => '手動提領',
        ];

        return view('admin.account_manipulation', [
            'user' => $user,
            'account' => $account,
            'types' => $types,
        ]);
    }

    public function storeManipulation(AccountManipulationRequest $request, Account $account)
    {
        $operator = auth()->user();
        $values = $request->validated();

        $this->AccountService->manipulate(
            $account,
            $operator,
            data_get($values, 'type'),
            data_get($values, 'amount'),
            data_get($values, 'unit_price'),
            data_get($values, 'note'),
            data_get($values, 'message')
        );

        return redirect()->route('admin.accounts.show', ['account' => $account->id])->with('flash_message', ['message' => '手動操作完成']);
    }

    public function search(Request $request)
    {
        $account_id = $request->input('account_id');
        $account = $this->AccountRepo->findOrFail($account_id);
        $keyword = data_get($request->input('search'), 'value');
        $query = $this->TransactionRepo
            ->queryTransaction([
                ['account_id', '=', $account_id],
            ], $keyword, true);

        $total = $this->TransactionRepo->countAllByAccount($account);
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->time = Carbon::parse($item->created_at)->toDateTimeString();
                $item->balance = formatted_coin_amount($item->balance, $item->account->coin);
                $item->amount = formatted_coin_amount($item->amount, $item->account->coin);
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
