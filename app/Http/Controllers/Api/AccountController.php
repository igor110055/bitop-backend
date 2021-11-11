<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\{
    Core\BadRequestError,
};
use App\Http\Resources\{
    AccountResource,
};
use App\Repos\Interfaces\{
    AccountRepo,
};


class AccountController extends AuthenticatedController
{
    public function __construct(
        AccountRepo $AccountRepo
    ) {
        parent::__construct();
        $this->coins = config('coin');
        $this->AccountRepo = $AccountRepo;
    }

    public function index(Request $request)
    {
        $coin = $request->input('coin');
        $user = auth()->user();
        if ($coin) {
            if (!in_array($coin, array_keys(hide_beta_coins($user, $this->coins)))) {
                throw new BadRequestError;
            }
            return new AccountResource($this->AccountRepo->findByUserCoinOrCreate($user, $coin));
        }
        return AccountResource::collection($this->AccountRepo->allByUserOrCreate($user));
    }
}
