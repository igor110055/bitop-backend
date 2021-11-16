<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
};
use App\Exceptions\{
    Core\BadRequestError,
};
use App\Http\Requests\{
    CreateBankAccountRequest,
};
use App\Repos\Interfaces\{
    BankAccountRepo,
};
use App\Http\Resources\BankAccountResource;

class BankAccountController extends AuthenticatedController
{
    public function __construct(BankAccountRepo $bank_account)
    {
        parent::__construct();
        $this->BankAccountRepo = $bank_account;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $is_verified = $request->input('is_verified', null);
        if ($is_verified === '1') {
            $is_verified = true;
        } elseif ($is_verified === '0') {
            $is_verified = false;
        } else {
            $is_verified = null;
        }

        return BankAccountResource::collection($this->BankAccountRepo
            ->getUserBankAccounts($user, $is_verified));
    }

    public function create(CreateBankAccountRequest $request)
    {
        $user = auth()->user();
        $input = $request->validated();
        $input['nationality'] = 'CN';
        $input['currency'] = ['CNY'];
        return new BankAccountResource($this->BankAccountRepo->create($user, $input));
    }

    public function delete(string $id)
    {
        $bank_account = $this->BankAccountRepo->findOrFail($id);
        $this->checkAuthorization($bank_account);

        if ($bank_account->deleted_at !== null) {
            throw new BadRequestError;
        }
        $this->BankAccountRepo->delete($bank_account);
        return response(null, 204);
    }

    public function getNationalitiesCurrencies()
    {
        $user = auth()->user();
        return $this->BankAccountRepo->getSupportMap($user->nationality);
    }

    protected function checkAuthorization($ba)
    {
        if (data_get($ba->owner, 'id') !== \Auth::id()) {
            throw new AccessDeniedHttpException;
        }
        return true;
    }
}
