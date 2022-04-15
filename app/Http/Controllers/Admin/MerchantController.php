<?php

namespace App\Http\Controllers\Admin;

use DB;
use Dec\Dec;
use Illuminate\Http\Request;

use App\Http\Requests\Admin\{
    MerchantCreateRequest,
    MerchantUpdateRequest,
};
use App\Models\{
    AdminAction,
    ExchangeRate,
    Merchant,
};
use App\Repos\Interfaces\{
    AdminActionRepo,
    MerchantRepo,
};
use App\Services\{
    ExchangeServiceInterface,
};


class MerchantController extends AdminController
{
    public function __construct(
        AdminActionRepo $AdminActionRepo,
        MerchantRepo $MerchantRepo,
        ExchangeServiceInterface $ExchangeService
    ) {
        parent::__construct();
        $this->AdminActionRepo = $AdminActionRepo;
        $this->MerchantRepo = $MerchantRepo;
        $this->ExchangeService = $ExchangeService;
        $this->coins = array_keys(config('coin'));

        $this->middleware(
            ['can:edit-merchants'],
            ['only' => [
                'update',
                'create',
                'store',
                'destroy',
                'editExchangeRate',
                'createExchangeRate',
            ]]
        );
    }

    public function index()
    {
        return view('admin.merchants', [
            'merchants' => $this->MerchantRepo->getAllMerchants()
        ]);
    }

    public function show(merchant $merchant)
    {
        return view('admin.merchant', [
            'merchant' => $merchant,
            'exchange_rates' => $this->ExchangeService
                ->getMerchantExchangeRates($merchant),
        ]);
    }

    public function update(merchant $merchant, merchantUpdateRequest $request)
    {
        $values = $request->validated();
        $this->MerchantRepo->update($merchant, $values);

        $this->AdminActionRepo->createByApplicable($merchant, [
            'admin_id' => \Auth::id(),
            'type' => AdminAction::TYPE_MERCHANT_UPDATE,
            'description' => json_encode($values),
        ]);

        return redirect()->route('admin.merchants.show', ['merchant' => $merchant->id])->with('flash_message', ['message' => '商戶資料編輯完成']);
    }

    public function create()
    {
        $merchant = new merchant;

        return view('admin.merchant_create', [
            'merchant' => $merchant,
            'page_title' => '新增商戶',
        ]);
    }

    public function store(MerchantCreateRequest $request)
    {
        $values = $request->validated();
        $values['id'] = strtolower($values['id']);

        try {
            $merchant = $this->MerchantRepo
                ->create($values);
        } catch (Exception $e) {
            return response('merchant id '.$values['id'].' has been used.', 409);
        }

        return redirect()->route('admin.merchants.show', ['merchant' => $merchant->id])->with('flash_message', ['message' => '商戶已新增']);
    }

    public function destroy(merchant $merchant, Request $request)
    {
        $merchant->delete();
        return redirect()->route('admin.merchants.index')->with('flash_message', ['message' => "商戶 {$merchant->id} 已刪除"]);
    }

    public function editExchangeRate(Merchant $merchant, $coin)
    {
        $currency = config('core.currency.base');

        $exchange_rate = $this->ExchangeService
            ->getMerchantExchangeRate($merchant, $coin);
        $system_exchange_rate = $this->ExchangeService
            ->get_system_exchange_rate($coin, $currency);

        return view('admin.merchant_exchange_rate', [
            'merchant' => $merchant,
            'coin' => $coin,
            'exchange_rate' => $exchange_rate,
            'system_exchange_rate' => $system_exchange_rate,
        ]);
    }

    public function createExchangeRate(Merchant $merchant, $coin, Request $request)
    {
        assert(in_array($coin, array_keys($this->coins)));

        $values = $request->validate([
            'type' => 'required|string|in:'.implode(",", ExchangeRate::TYPES),
            'bid' => 'numeric|nullable|required_if:type,'.ExchangeRate::TYPE_FIXED,
            'ask' => 'numeric|nullable|required_if:type,'.ExchangeRate::TYPE_FIXED,
            'bid_diff' => 'numeric|nullable|required_if:type,'.ExchangeRate::TYPE_FLOATING,
            'ask_diff' => 'numeric|nullable|required_if:type,'.ExchangeRate::TYPE_FLOATING,
            'diff' => 'numeric|nullable|required_if:type,'.ExchangeRate::TYPE_DIFF,
        ]);

        $values['coin'] = $coin;
        $numeric_fields = ['bid', 'ask', 'bid_diff', 'ask_diff', 'diff'];
        foreach($numeric_fields as $field) {
            $values[$field] = (is_null($values[$field])) ? '0.00' : $values[$field];
        }

        $this->MerchantRepo
            ->createExchangeRate($merchant, $values);

        return redirect()
                ->route('admin.merchants.show', ['merchant' => $merchant->id])
                ->with('flash_message', ['message' => "設定完成"]);

    }
}