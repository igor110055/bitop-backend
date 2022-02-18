<?php

namespace App\Http\Controllers\Admin;

use DB;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\{
    FeeSettingRequest,
};
use App\Repos\Interfaces\{
    FeeSettingRepo,
    FeeCostRepo,
    GroupRepo,
    ConfigRepo,
};
use App\Services\{
    FeeServiceInterface,
    ExchangeServiceInterface,
};
use App\Models\{
    FeeSetting,
    Config,
};

class FeeSettingController extends AdminController
{
    public function __construct(
        FeeSettingRepo $FeeSettingRepo,
        FeeCostRepo $FeeCostRepo,
        GroupRepo $GroupRepo,
        ConfigRepo $ConfigRepo
    ) {
        parent::__construct();
        $this->FeeSettingRepo = $FeeSettingRepo;
        $this->FeeCostRepo = $FeeCostRepo;
        $this->GroupRepo = $GroupRepo;
        $this->ConfigRepo = $ConfigRepo;
        $this->coins = array_keys(config('coin'));

        $this->middleware(
            ['can:edit-fees'],
            ['only' => [
            ]]
        );

        $this->middleware(
            ['role:super-admin'],
            ['only' => [
                'storeFixed',
                'edit',
                'store',
            ]]
        );
    }

    public function index()
    {
        $range_types = FeeSetting::RANGE_TYPES;
        $fix_types = FeeSetting::FIX_TYPES;

        foreach ($range_types as $type) {
            foreach ($this->coins as $coin) {
                $range_settings[$type][$coin] = $this->FeeSettingRepo
                    ->get($coin, $type, null);
            }
        }

        foreach ($fix_types as $type) {
            foreach ($this->coins as $coin) {
                $fix_settings[$type][$coin] = $this->FeeSettingRepo
                    ->get($coin, $type, null);
            }
        }

        foreach ($this->coins as $coin) {
            $fee_cost = data_get($this->FeeCostRepo->getLatest($coin), 'cost');
            $fee_costs[$coin] = is_null($fee_cost) ? $fee_cost : trim_zeros($fee_cost);
            $base = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, "$coin.base");
            $fee_base[$coin] = is_null($base) ? $base : trim_zeros($base);
        }

        return view('admin.fee_settings', [
            'range_settings' => $range_settings,
            'fix_settings' => $fix_settings,
            'withdrawal_fee_costs' => $fee_costs,
            'withdrawal_fee_base' => $fee_base,
            'withdrawal_fee' => $this->getWithdrawalFee(),
        ]);
    }

    protected function getWithdrawalFee()
    {
        $FeeService = app()->make(FeeServiceInterface::class);
        $ExchangeService = app()->make(ExchangeServiceInterface::class);
        foreach ($this->coins as $coin) {
            $amount = $FeeService->getWithdrawalFee($coin);
            $fee[$coin]['amount'] = trim_zeros($amount);
            $fee[$coin]['price'] = $ExchangeService->coinToBaseValue($coin, $amount);
        }
        return $fee;
    }

    public function data(Request $request)
    {
        if ($request->input('applicable_id')) {
            $applicable = $this->GroupRepo
                ->findOrFail($request->input('applicable_id'));
        } else {
            $applicable = null;
        }
        $response = $this->FeeSettingRepo
            ->get($request->input('coin'), $request->input('type'), $applicable);
        return response()->json(['data' => $response]);
    }

    public function edit($type, $coin)
    {
        if (in_array($type, FeeSetting::RANGE_TYPES)) {
            return view('admin.fee_settings_edit', [
                'type' => $type,
                'coin' => $coin,
                'data' => [
                    'type' => $type,
                    'coin' => $coin,
                ],
            ]);
        } elseif (in_array($type, FeeSetting::FIX_TYPES)) {
            return view('admin.withdrawal_fee_edit', [
                'type' => $type,
                'coin' => $coin,
                'base' => $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, "$coin.base"),
                'discount' => data_get($this->FeeSettingRepo->getFixed($coin, $type, null), 'value', 100),
            ]);
        }
    }

    public function storeFixed(Request $request)
    {
        $values = $request->validate([
            'type' => 'required|in:'.implode(",", FeeSetting::FIX_TYPES),
            'coin' => 'required|in:'.implode(",", $this->coins),
            'discount' => 'required|numeric',
            'applicable_id' => 'nullable|string',
        ]);
        if ($id = data_get($values, 'applicable_id')) {
            $applicable = $this->GroupRepo->findOrFail($id);
        } else {
            $applicable = null;
        }
        DB::transaction(function () use ($values, $applicable) {
            $this->FeeSettingRepo->inactivate($values['coin'], $values['type'], $applicable);
            $this->FeeSettingRepo->setFixed(
                $values['coin'],
                $values['type'],
                $values['discount'],
                $applicable
            );
        });
        return redirect()->route('admin.fee-settings.index');
    }

    public function store(FeeSettingRequest $request)
    {
        $values = $request->validated();
        if (isset($values['applicable_id'])) {
            $applicable = $this->GroupRepo
                ->findOrFail($values['applicable_id']);
        } else {
            $applicable = null;
        }
        DB::transaction(function () use ($values, $applicable) {
            $this->FeeSettingRepo->inactivate($values['coin'], $values['type'], $applicable);
            if (!empty($values['ranges'])) {
                return $this->FeeSettingRepo->set(
                    $values['coin'],
                    $values['type'],
                    $values['ranges'],
                    $applicable
                );
            }
        });
        return response(null, 204);
    }
}
