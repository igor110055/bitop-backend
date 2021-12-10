<?php

namespace App\Http\Controllers\Admin;

use DB;
use Illuminate\Http\Request;

use App\Http\Requests\Admin\{
    VerifyBankAccountRequest,
};
use App\Models\{
    BankAccount,
    AdminAction,
};
use App\Repos\Interfaces\{
    BankAccountRepo,
    AdminActionRepo,
};
use App\Notifications\{
    BankAccountReviewNotification,
};

class BankAccountController extends AdminController
{
    public function __construct(
        BankAccountRepo $BankAccountRepo,
        AdminActionRepo $AdminActionRepo
    ) {
        parent::__construct();
        $this->BankAccountRepo = $BankAccountRepo;
        $this->AdminActionRepo = $AdminActionRepo;
        $this->coins = array_keys(config('coin'));

        $this->middleware(
            ['can:edit-bank-accounts'],
            ['only' => [
                'verify',
            ]]
        );
    }

    public function index(Request $request)
    {
        return view('admin.bank_accounts', [
            'status' => $request->input('status'),
        ]);
    }

    public function show(BankAccount $bank_account)
    {
        return view('admin.bank_account', [
            'bank_account' => $bank_account,
            'user' => $bank_account->owner,
            'bank' => $bank_account->bank,
            'reject_reasons' => __('messages.bank_account.reject_reasons'),
            'admin_actions' => $bank_account->admin_actions,
        ]);
    }

    public function search(Request $request)
    {
        $status = $request->input('status', 'active');
        $keyword = data_get($request->input('search'), 'value');
        $query = $this->BankAccountRepo->getFilteringQuery($status, $keyword);

        $total = $this->BankAccountRepo->getAllCount();
        $result = [
            'recordsTotal' => $total,
            'recordsFiltered' => $query->count(),
            'data' => $query
                ->skip(clamp_query($request->input('start'), 0, $total))
                ->take(clamp_query($request->input('length'), 10, 100))
                ->get(),
        ];
        $draw = $request->input('draw');
        if (is_numeric($draw)) {
            $result['draw'] = (int)$draw;
        }
        return $result;
    }

    public function verify(BankAccount $bank_account, VerifyBankAccountRequest $request)
    {
        $user = $bank_account->owner;
        $locale = $user->preferred_locale;
        $values = $request->validated();
        $reasons = data_get($values, 'reasons', []);
        $action = data_get($values, 'action');
        $other_reason = data_get($values, 'other_reason', '');

        if ($action === 'approve') {
            $this->BankAccountRepo->approve($bank_account);
            $this->AdminActionRepo->createByApplicable($bank_account, [
                'admin_id' => \Auth::id(),
                'type' => AdminAction::TYPE_APPROVE_BANK_ACCOUNT,
                'description' => '',
            ]);
        }
        if ($action === 'reject') {
            $localized_reasons = array_map(function($item) use ($locale) {
                return __("messages.bank_account.reject_reasons.{$item}", [], $locale);
            }, $reasons);
            if ($other_reason) {
                $localized_reasons[] = $other_reason;
            }

            $this->BankAccountRepo->reject($bank_account);
            $this->AdminActionRepo->createByApplicable($bank_account, [
                'admin_id' => \Auth::id(),
                'type' => AdminAction::TYPE_REJECT_BANK_ACCOUNT,
                'description' => implode(', ', $localized_reasons),
            ]);
        }

        $user->refresh();
        if (in_array($action, ['approve', 'reject'])) {
            $result_notification = new BankAccountReviewNotification($action, $bank_account->bank, $reasons, $other_reason);
            $user->notify($result_notification);
        }

        if ($next = $this->BankAccountRepo->getNextToReview()) {
            $next_url = route('admin.bank-accounts.show', ['bank_account' => $next->id]);
        }

        return response()->json([
            'result' => 'done',
            'next' => $next ? $next_url : null,
        ]);
    }

}
