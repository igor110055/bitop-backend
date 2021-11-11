<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Repos\Interfaces\{
    BankRepo,
};
use App\Exceptions\{
    Core\BadRequestError,
};
use App\Http\Resources\BankResource;

class BankController extends AuthenticatedController
{
    public function __construct(BankRepo $BankRepo)
    {
        parent::__construct();
        $this->BankRepo = $BankRepo;
    }

    public function getBankList(Request $request)
    {
        $prime = array_keys(config('core')['nationality']);
        if ($nationality = $request->input('nationality')) {
            if (!in_array($nationality, $prime)) {
                throw new BadRequestError;
            }
            return BankResource::collection($this->BankRepo->getBankListByNationality($nationality));
        }
        return BankResource::collection($this->BankRepo->getBankList());
    }
}
