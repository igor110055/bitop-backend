<?php

namespace App\Http\Controllers\Admin;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\{
    VerifyUserRequest,
    OrderSearchRequest,
    AdvertisementSearchRequest,
    TransferRequest,
    SearchRequest,
};

use App\Exceptions\{
    Account\InsufficientBalanceError,
};

use App\Models\{
    User,
    Authentication,
    Order,
    Advertisement,
    Limitation,
    UserLock,
    AdminAction,
    Transaction,
};
use App\Notifications\{
    AuthResultNotification,
};
use App\Services\{
    AccountServiceInterface,
    TwoFactorAuthServiceInterface,
};
use App\Repos\Interfaces\{
    AccountRepo,
    BankAccountRepo,
    UserRepo,
    AuthenticationRepo,
    GroupRepo,
    OrderRepo,
    AdvertisementRepo,
    LimitationRepo,
    AdminActionRepo,
    RoleRepo,
};

class UserController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(
        AccountRepo $AccountRepo,
        BankAccountRepo $BankAccountRepo,
        UserRepo $UserRepo,
        AuthenticationRepo $AuthenticationRepo,
        OrderRepo $OrderRepo,
        AdvertisementRepo $AdvertisementRepo,
        LimitationRepo $LimitationRepo,
        AdminActionRepo $AdminActionRepo,
        RoleRepo $RoleRepo,
        AccountServiceInterface $AccountService,
        TwoFactorAuthServiceInterface $TwoFactorAuthService
    ) {
        parent::__construct();
        $this->AccountRepo = $AccountRepo;
        $this->BankAccountRepo = $BankAccountRepo;
        $this->UserRepo = $UserRepo;
        $this->AuthenticationRepo = $AuthenticationRepo;
        $this->OrderRepo = $OrderRepo;
        $this->AdvertisementRepo = $AdvertisementRepo;
        $this->LimitationRepo = $LimitationRepo;
        $this->AdminActionRepo = $AdminActionRepo;
        $this->RoleRepo = $RoleRepo;
        $this->AccountService = $AccountService;
        $this->TwoFactorAuthService = $TwoFactorAuthService;
        $this->tz = config('core.timezone.default');
        $this->dateFormat = 'Y-m-d';

        $this->middleware(
            ['can:edit-users'],
            ['only' => [
                'edit',
                'update',
                'editLimitations',
                'storeLimitation',
                'createLock',
                'storeLock',
                'unlock',
                'deactivateTFA',
            ]]
        );

        $this->middleware(
            ['can:verify-users'],
            ['only' => [
                'verify',
            ]]
        );

        $this->middleware(
            ['can:edit-limitations'],
            ['only' => [
                'editLimitations',
                'storeLimitation',
            ]]
        );

        $this->middleware(
            ['can:edit-accounts'],
            ['only' => [
                'createTransfer',
                'storeTransfer',
            ]]
        );

        $this->middleware(
            ['can:edit-auth'],
            ['only' => [
                'authorizeAdmin',
                'updateRole',
            ]]
        );

        $this->middleware(
            ['role:super-admin'],
            ['only' => [
                'authorizeTester',
            ]]
        );
    }

    public function index(Request $request)
    {
        return view('admin.users', [
            'status' => $request->input('status'),
        ]);
    }

    public function show(User $user)
    {
        if ($auth = $this->AuthenticationRepo->getLatestAuth($user)) {
            $is_username_available = $this->UserRepo->checkUsernameAvailability($auth->username, $user);
        }
        $role = $this->RoleRepo->getUserRole($user, 'web');
        return view('admin.user', [
            'user' => $user,
            'accounts' => $user->accounts,
            'group' => $user->group,
            'bank_accounts' => $this->BankAccountRepo->getUserBankAccounts($user, null, true),
            'auth' => $auth,
            'files' => $this->AuthenticationRepo->getLatestAuthFiles($user),
            'reject_reasons' => __('messages.authentication.reject_reasons'),
            'is_username_available' => isset($is_username_available) ? $is_username_available : null,
            'admin_lock' => $this->UserRepo->getUserLocks($user, UserLock::ADMIN),
            'user_locks' => $this->UserRepo->getUserLocks($user),
            'is_root' => $user->is_root,
            'role' => data_get($role, 'name'),
        ]);
    }

    public function edit(User $user, GroupRepo $GroupRepo)
    {
        $groups = $GroupRepo->getJoinableGroupIds()->toArray();
        return view('admin.user_edit', [
            'user' => $user,
            'groups' => array_combine($groups, $groups),
        ]);
    }

    public function update(User $user, GroupRepo $GroupRepo, Request $request)
    {
        $new_group = $GroupRepo->findOrFail($request->input('group_id'));
        $groups = $user->groups;

        if ($groups->isNotEmpty()) {
            foreach ($groups as $group) {
                if ($group->id !== $new_group->id) {
                    return redirect()->route('admin.users.edit', ['user' => $user])->with('flash_message', ['message' => "用戶為群組 {$group->name} 的群主，無法變更其群組。", 'class' => 'danger']);
                }
            }
        }

        if (!$this->UserRepo->checkUsernameAvailability($request->input('name'), $user)) {
            return redirect()->route('admin.users.edit', ['user' => $user])->with('flash_message', ['message' => "顯示名稱已被其他用戶註冊", 'class' => 'danger']);
        }

        $update = [
            'username' => $request->input('name'),
            'group_id' => $request->input('group_id'),
        ];
        $this->UserRepo->update($user, $update);

        $password = $request->input('password');
        if (!empty($password)) {
            $this->UserRepo->setPassword($user, $password);
        }

        $this->AdminActionRepo->createByApplicable($user, [
            'admin_id' => \Auth::id(),
            'type' => AdminAction::TYPE_USER_UPDATE,
            'description' => json_encode($update),
        ]);

        return redirect()->route('admin.users.show', ['user' => $user->id])->with('flash_message', ['message' => '編輯完成']);
    }

    public function verify(Authentication $auth, VerifyUserRequest $request)
    {
        $user = $auth->owner;
        $values = $request->validated();
        $reasons = data_get($values, 'reasons', []);
        $action = data_get($values, 'action');
        $other_reason = data_get($values, 'other_reason');

        if ($action === 'approve') {
            try {
                $this->AuthenticationRepo->approve($auth);
            } catch (\Throwable $e) {
                $action = 'reject';
                $reasons = [Authentication::REASON_USERNAME_EXISTED];
            }
        }
        if ($action === 'reject') {
            $this->AuthenticationRepo->reject($auth);
        }

        $user->refresh();
        if (in_array($user->authentication_status, [Authentication::PASSED, Authentication::REJECTED])) {
            $result_notification = new AuthResultNotification($reasons, $other_reason);
            $user->notify($result_notification);
        }

        if ($next_user = $this->UserRepo->getNextUserForAuthentication()) {
            $next_user_url = route('admin.users.show', ['user' => $next_user->id]);
        }

        return response()->json([
            'result' => 'done',
            'next' => $next_user ? $next_user_url : null,
        ]);
    }

    public function search(Request $request)
    {
        $group = $request->input('group');
        $status = $request->input('status');
        $keyword = data_get($request->input('search'), 'value');
        $query = $this->UserRepo->getFilteringQuery($group, $status, $keyword);

        $total_users_count = $this->UserRepo->getUserCount();
        $result = [
            'recordsTotal' => $total_users_count,
            'recordsFiltered' => $query->count(),
            'data' => $query
                ->skip(clamp_query($request->input('start'), 0, $total_users_count))
                ->take(clamp_query($request->input('length'), 10, 100))
                ->get(),
        ];
        $draw = $request->input('draw');
        if (is_numeric($draw)) {
            $result['draw'] = (int)$draw;
        }
        return $result;
    }

    public function selectSearch(Request $request, string $keyword = null)
    {
        $result = [];
        $keyword = $request->input('term');
        if (is_string($keyword) and strlen($keyword) > 2) {
            $result = $this->UserRepo
                ->getFilteringQuery(null, null, $keyword)
                ->get();
        }
        return response()->json(['results' => $result]);
    }

    public function orderList(User $user)
    {
        $dateFormat = 'Y-m-d';
        return view('admin.user_orders', [
            'from' => Carbon::parse('today -10 days', $this->tz)->format($dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($dateFormat),
            'status' => [
                'all' => 'All',
                Order::STATUS_PROCESSING => 'Processing',
                Order::STATUS_CLAIMED => 'Claimed',
                Order::STATUS_COMPLETED => 'Completed',
                Order::STATUS_CANCELED => 'Canceled',
            ],
            'express' => [
                'all' => 'All',
                '0' => '一般交易',
                '1' => '快捷交易',
            ],
            'user' => $user,
        ]);
    }

    public function advertisementList(User $user)
    {
        $dateFormat = 'Y-m-d';
        return view('admin.advertisements', [
            'from' => null,//Carbon::parse('today -10 days', $this->tz)->format($dateFormat),
            'to' => null,//Carbon::parse('today', $this->tz)->format($dateFormat),
            'status' => [
                'all' => 'All',
                Advertisement::STATUS_AVAILABLE => 'Available',
                Advertisement::STATUS_UNAVAILABLE => 'Unavailable',
                Advertisement::STATUS_COMPLETED => 'Completed',
                Advertisement::STATUS_DELETED => 'Deleted',
            ],
            'express' => [
                'all' => 'All',
                '0' => '一般交易',
                '1' => '快捷交易',
            ],
            'user' => $user,
        ]);
    }

    public function getOrders(User $user, OrderSearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $status = $request->input('status');
        $from = Carbon::parse(data_get($values, 'from', 'today - 10 days'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $is_express = data_get($values, 'is_express');

        $condition = [];
        if ($status !== 'all') {
            $condition[] = ['status', '=', $status];
        }
        $condition[] = ['created_at', '>=', $from];
        $condition[] = ['created_at', '<', $to];
        if ($is_express === '1') {
            $condition[] = ['is_express', '=', true];
        } elseif ($is_express === '0') {
            $condition[] = ['is_express', '=', false];
        }

        $query = $this->OrderRepo->queryOrder($condition, $keyword, $user);
        $total = $this->OrderRepo->getUserOrdersCount($user);
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
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

    public function getAdvertisements(User $user, AdvertisementSearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $status = data_get($values, 'status');
        $is_express = data_get($values, 'is_express');

        $condition = [];

        if ($status !== 'all') {
            $condition[] = ['status', '=', $status];
        }
        if (!empty(data_get($values, 'from'))) {
            $from = Carbon::parse(data_get($values, 'from'), $this->tz);
            $condition[] = ['created_at', '>=', $from];
        }
        if (!empty(data_get($values, 'to'))) {
            $to = Carbon::parse(data_get($values, 'to'), $this->tz)->addDay();
            $condition[] = ['created_at', '<', $to];
        }
        if ($is_express === '1') {
            $condition[] = ['is_express', '=', true];
        } elseif ($is_express === '0') {
            $condition[] = ['is_express', '=', false];
        }

        $query = $this->AdvertisementRepo->queryAdvertisement($condition, $keyword, $user);
        $total = $this->AdvertisementRepo->getUserAdsCount($user);

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->remaining_amount = formatted_coin_amount($item->remaining_amount);
                return $item;
            });
        return $this->draw(
            $this->result(
                $total,
                $query->count(),
                $data
            )
        );
    }

    public function getLimitations(User $user)
    {
        $types = Limitation::TYPES;
        $coins = array_keys(config('coin'));
        foreach ($types as $type) {
            foreach ($coins as $coin) {
                $limitations[$type][$coin] = $this->LimitationRepo
                    ->getLatestLimitation($type, $coin, $user);
            }
        }
        return view('admin.limitations', [
            'limitations' => $limitations,
            'user' => $user,
        ]);
    }

    public function editLimitations(User $user, $type, $coin)
    {
        $limitation = $this->LimitationRepo->getLatestLimitation($type, $coin, $user);
        return view('admin.limitation_edit', [
            'type' => $type,
            'coin' => $coin,
            'limitation' => $limitation,
            'user' => $user,
            'active' => $this->LimitationRepo->getLatestLimitationByClass($type, $coin, $user) ? true : false,
        ]);
    }

    public function storeLimitation(User $user, Request $request)
    {
        $values = $request->all();

        # reset
        if ($request->input('reset')) {
            DB::transaction(function () use ($values, $user) {
                $limitation = $this->LimitationRepo->getLatestLimitationByClass($values['type'], $values['coin'], $user);
                $limitation->update(['is_active' => false]);
            });
            return redirect()->route('admin.users.limitations', ['user' => $user])->with('flash_message', ['message' => '取消設定完成']);
        }

        # store
        DB::transaction(function () use ($values, $user) {
            if ($limitation = $this->LimitationRepo->getLatestLimitationByClass($values['type'], $values['coin'], $user)) {
                if ($limitation->is_active) {
                    $limitation->update(['is_active' => false]);
                }
            }
            $user->limitations()->create([
                'coin' => $values['coin'],
                'type' => $values['type'],
                'min' => $values['min'],
                'max' => $values['max'],
            ]);
        });
        return redirect()->route('admin.users.limitations', ['user' => $user])->with('flash_message', ['message' => '設定完成']);
    }

    public function createLock(User $user)
    {
        $types = [];
        foreach (UserLock::FEATURE_TYPES as $type) {
            $types[$type] = __("messages.user.lock_type.{$type}");
        }
        return view('admin.user_feature_lock', [
            'user' => $user,
            'types' => $types,
        ]);
    }

    public function storeLock(User $user, Request $request)
    {
        $values = $request->validate([
            'type' => 'required|in:'.implode(',', UserLock::FEATURE_TYPES),
            'expired_time' => 'required_unless:type,admin|string',
            'description' => 'required|string',
        ]);
        if (data_get($values, 'expired_time')) {
            $expired_time = Carbon::parse($values['expired_time'], $this->tz);
        } else {
            $expired_time = null;
        }

        if ($user->is_root) {
            return redirect()->route('admin.users.show', ['user' => $user->id]);
        }

        # store
        DB::transaction(function () use ($values, $user, $expired_time) {
            if ($lock = $this->UserRepo->getUserLock($user, $values['type'])) {
                return redirect()->route('admin.users.show', ['user' => $user->id]);
            }

            $userlock = $this->UserRepo->createUserLock($user, $values['type'], $expired_time);
            $this->AdminActionRepo->createByApplicable($userlock, [
                'admin_id' => \Auth::id(),
                'type' => AdminAction::TYPE_USER_LOCK,
                'description' => $values['description'],
            ]);
        });
        return redirect()->route('admin.users.show', ['user' => $user->id])->with('flash_message', ['message' => '設定完成']);
    }

    public function unlock(Request $request)
    {
        $values = $request->validate([
            'id' => 'required',
            'description' => 'required|string',
        ]);

        DB::transaction(function () use ($values) {
            $userlock = UserLock::find($values['id']);
            $userlock->update(['is_active' => false]);
            $this->AdminActionRepo->createByApplicable($userlock, [
                'admin_id' => \Auth::id(),
                'type' => AdminAction::TYPE_USER_UNLOCK,
                'description' => $values['description'],
            ]);
        });
        $request->session()->flash('flash_message', ['message' => '解鎖完成']);
        return response('1', 200);
    }

    public function authorizeAdmin(User $user)
    {
        if ($user->is_admin) {
            if (!$user->is_root) {
                $this->UserRepo->update($user, ['is_admin' => false]);
                $user->syncroles([]);
            }
        } else {
            $this->UserRepo->update($user, ['is_admin' => true]);
            $user->syncroles(['viewer']);
        }
        return redirect()->route('admin.users.show', ['user' => $user])->with('flash_message', ['message' => '管理員設定完成']);
    }

    public function authorizeTester(User $user)
    {
        if ($user->is_tester) {
            $this->UserRepo->update($user, ['is_tester' => false]);
        } else {
            $this->UserRepo->update($user, ['is_tester' => true]);
        }
        return redirect()->route('admin.users.show', ['user' => $user])->with('flash_message', ['message' => '測試權限設定完成']);
    }

    public function updateRole(User $user, Request $request)
    {
        $role = $request->input('role');
        if ($user->is_root or $role === 'super-admin') {
            return redirect()->route('admin.users.show', ['user' => $user->id]);
        }
        $user->syncRoles([$role]);
        return redirect()->route('admin.users.show', ['user' => $user])->with('flash_message', ['message' => '權限設定完成']);
    }

    public function deactivateTFA(User $user, Request $request)
    {
        if ($user->is_root and !auth()->user()->is_root) {
            return redirect()->route('admin.users.show', ['user' => $user])->with('flash_message');
        }
        $this->TwoFactorAuthService->deactivateWithoutVerify($user, $request->input('description'));
        return redirect()->route('admin.users.show', ['user' => $user])->with('flash_message', ['message' => '強制關閉二次驗證完成']);
    }

    public function createTransfer(User $user)
    {
        $coins = array_keys(config('coin'));
        $coins = array_combine($coins, $coins);
        return view('admin.user_transfer', [
            'src_user' => $user,
            'coins' => $coins,
        ]);
    }

    public function storeTransfer(TransferRequest $request, User $user)
    {
        $operator = auth()->user();
        $values = $request->validated();

        try {
            $src_account = DB::transaction(function () use ($user, $values, $operator) {
                $coin = data_get($values, 'coin');
                $src_account = $this->AccountRepo->findByUserCoinOrCreate($user, $coin);
                $dst_user = $this->UserRepo->findOrFail($values['dst_user_id']);
                $dst_account = $this->AccountRepo->findByUserCoinOrCreate($dst_user, $coin);

                $this->AccountService->manipulate(
                    $src_account,
                    $operator,
                    Transaction::TYPE_MANUAL_WITHDRAWAL,
                    data_get($values, 'amount'),
                    null,
                    data_get($values, 'note'),
                    data_get($values, 'src_message')
                );

                $this->AccountService->manipulate(
                    $dst_account,
                    $operator,
                    Transaction::TYPE_MANUAL_DEPOSIT,
                    data_get($values, 'amount'),
                    null,
                    data_get($values, 'note'),
                    data_get($values, 'dst_message')
                );
                return $src_account;
            });
        } catch (InsufficientBalanceError $e) {
            return redirect()->route('admin.users.transfers.create', ['user' => $user->id])->with('flash_message', ['message' => '轉出帳戶餘額不足']);
        } catch (\Throwable $e) {
            return redirect()->route('admin.users.transfers.create', ['user' => $user->id])->with('flash_message', ['message' => '錯誤的操作']);
        }

        return redirect()->route('admin.accounts.show', ['account' => $src_account])->with('flash_message', ['message' => '手動劃轉完成']);
    }

    public function logList(User $user)
    {
        return view('admin.user_logs', [
            'user' => $user,
            'from' => Carbon::parse('today - 3 months', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
        ]);
    }

    public function getLogs(User $user, SearchRequest $request)
    {
        $values = $request->validated();
        $from = Carbon::parse(data_get($values, 'from', 'today - 3 months'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $condition = $this->timeIntervalCondition('created_at', $from, $to);
        $query = $this->UserRepo
            ->queryUserLogs($user, $condition);
        $total = $this->UserRepo->countAllLogs($user);
        $filtered = $query->count();
        $data = $this->queryPagination($query, $total);

        return $this->draw(
            $this->result(
                $total,
                $filtered,
                $data
            )
        );
    }
}
