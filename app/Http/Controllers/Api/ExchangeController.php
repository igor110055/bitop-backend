<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\{
    CoinPriceRequest,
};
use App\Models\{
    Advertisement,
};
use App\Services\{
    ExchangeServiceInterface,
};

class ExchangeController extends AuthenticatedController
{
    public function __construct(ExchangeServiceInterface $es)
    {
        parent::__construct();
        $this->ExchangeService = $es;
    }

    public function getCoinPrice(CoinPriceRequest $request)
    {
        $user = auth()->user();

        $action = $request->action;
        /* this agent feature is not activated now
            if ($user->is_agent) {
            if ($action === Advertisement::TYPE_BUY) {
                $action = Advertisement::TYPE_SELL;
            } else {
                $action = Advertisement::TYPE_BUY;
            }
        } */

        $result = $this->ExchangeService
            ->coinToCurrency(
                $user,
                $request->coin,
                data_get($request, 'currency', config('core.currency.base')),
                $action,
                data_get($request, 'amount', '1')
            );

        return [
            'coin' => $result['coin'],
            'currency' => $result['currency'],
            'action' => $request->action,
            'amount' => $result['coin_amount'],
            'unit_price' => $result['unit_price'],
            'price' => $result['currency_amount'],
        ];
    }
}
