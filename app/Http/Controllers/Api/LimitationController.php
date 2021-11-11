<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LimitationRequest;
use App\Services\{
    AccountServiceInterface,
    ExchangeServiceInterface,
};
use App\Repos\Interfaces\{
    LimitationRepo,
};
use App\Models\Config;
use App\Http\Resources\LimitationResource;

class LimitationController extends AuthenticatedController
{
    public function __construct(
        LimitationRepo $lr,
        AccountServiceInterface $as,
        ExchangeServiceInterface $es
    ) {
        parent::__construct();
        $this->LimitationRepo = $lr;
        $this->AccountService = $as;
        $this->ExchangeService = $es;
    }

    public function show(LimitationRequest $request)
    {
        $user = auth()->user();
        $values = $request->validated();
        $coin = $values['coin'];
        $type = $values['type'];
        $limitation = $this->LimitationRepo
            ->getLatestLimitation(
                $type,
                $coin,
                $user
            );

        $args = $this->AccountService->getDailyWithdrawalLimitationArguments($user);

        if ($limitation) {
            return (new LimitationResource($limitation))->withUSDLimit($args);
        } else {
            return [
                'type' => $type,
                'coin' => $coin,
                'min' => '0.000000',
                'max' => '0.000000',
                'remain_max' => $this->ExchangeService->USDTToCoin($args['daily_remain'], $coin),
                'USD' => $args,
            ];
        }
    }
}
