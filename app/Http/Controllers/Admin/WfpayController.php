<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Repos\Interfaces\WfpayAccountRepo;
use App\Models\Wfpayment;

class WfpayController extends AdminController
{
    public function __construct(
        WfpayAccountRepo $WfpayAccountRepo
    ) {
        parent::__construct();
        $this->WfpayAccountRepo = $WfpayAccountRepo;
        $this->middleware(['can:edit-configs']);
    }

    public function index()
    {
        $accounts = $this->WfpayAccountRepo->getByRank(false);
        $methods = Wfpayment::$methods;
        return view('admin.wfpay_accounts', [
            'accounts' => $accounts,
            'methods' => $methods,
        ]);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $id = data_get($input, 'id');
        $wfpay_account = $this->WfpayAccountRepo->findOrFail($id);
        $update = [
            'is_active' => data_get($input, 'is_active') === '1',
            'rank' => data_get($input, 'rank'),
            'configs' => [
                'payment_methods' => data_get($input, 'methods', []),
            ]
        ];
        $this->WfpayAccountRepo->update($wfpay_account, $update);
        return redirect()->route('admin.wfpays.index')->with('flash_message', ['message' => '設定完成']);
    }
}
