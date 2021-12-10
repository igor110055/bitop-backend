<?php

namespace App\Http\Controllers\Admin;

use Dec\Dec;
use Illuminate\Http\Request;

use App\Http\Requests\Admin\{
    GroupCreateRequest,
    GroupUpdateRequest,
};
use App\Models\{
    Group,
};
use App\Repos\Interfaces\{
    CurrencyExchangeRateRepo,
    GroupRepo,
    ShareSettingRepo,
};


class ExchangeRateController extends AdminController
{
    public function __construct(
        CurrencyExchangeRateRepo $CurrencyExchangeRateRepo,
        GroupRepo $GroupRepo,
        ShareSettingRepo $ShareSettingRepo
    ) {
        parent::__construct();
        $this->CurrencyExchangeRateRepo = $CurrencyExchangeRateRepo;
        $this->GroupRepo = $GroupRepo;
        $this->ShareSettingRepo = $ShareSettingRepo;

        $this->middleware(
            ['role:super-admin']
        );
    }

    public function index()
    {
        return view('admin.exchange_rates', ['group' => null]);
    }

    public function get(Group $group = null)
    {
        $base_currency = config('core.currency.base');
        $currencies = config('core.currency.all');
        $data = [];

        foreach ($currencies as $currency) {
            $currency_exchange_rate = $this->CurrencyExchangeRateRepo
                ->getLatest($currency);
            $data[] = [
                'currency' => $currency,
                'is_editable' => ($currency !== $base_currency),
                'rate' => $currency_exchange_rate,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function create(Request $request)
    {
        $base_currency = config('core.currency.base');
        $currencies = config('core.currency.all');

        # Remove $base_currency from $currencies
        $currencies = array_diff($currencies, [$base_currency]);

        $rules = [
            'currency' => 'required|string|in:'.implode(',', $currencies),
            'bid' => 'required|numeric|min:0|lt:ask',
            'ask' => 'required|numeric|min:0',
        ];

        $values = $request->validate($rules);

        $this->CurrencyExchangeRateRepo
            ->create($values['currency'], $values['bid'], $values['ask']);

        return response(null, 204);
    }

}
