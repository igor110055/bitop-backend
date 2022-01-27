<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
};
use App\Http\Controllers\Traits\SecurityCodeTrait;
use App\Exceptions\WrongAddressFormatError;
use App\Services\WalletServiceInterface;
use App\Repos\Interfaces\AddressRepo;
use App\Http\Requests\{
    CreateAddressRequest,
    GetAddressRequest,
    ValidateAddressRequest,
};
use App\Http\Resources\AddressResource;
use App\Models\Address;

class AddressController extends AuthenticatedController
{
    use SecurityCodeTrait;

    public function __construct(AddressRepo $AddressRepo, WalletServiceInterface $WalletService)
    {
        parent::__construct();
        $this->AddressRepo = $AddressRepo;
        $this->WalletService = $WalletService;
        $this->middleware(
            'userlock',
            ['only' => ['create', 'delete', 'bulkDelete']]
        );
    }

    public function getAddresses(GetAddressRequest $request)
    {
        $values = $request->validated();
        $addresses = $this->AddressRepo
            ->getUserAddresses(
                auth()->user(),
                data_get($values, 'coin')
            );

        return AddressResource::collection($addresses);
    }

    public function create(CreateAddressRequest $request)
    {
        $values = $request->validated();
        $user = auth()->user();
        # check security_code
        $this->checkSecurityCode($user, $values['security_code']);

        # validate address
        $res = $this->WalletService->getAddressValidation($values['coin'], $values['address']);
        if ($values['address'] !== data_get($res, 'address')) {
            throw new WrongAddressFormatError;
        }
        return new AddressResource($this->AddressRepo->createByUser(auth()->user(), $values));
    }

    public function delete(string $id)
    {
        $address = $this->AddressRepo->findOrFail($id);
        $this->checkAuthorization($address);
        $this->AddressRepo->delete($address);
        return response(null, 204);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('id');
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if ($address = $this->AddressRepo->find($id)) {
                    try {
                        $this->checkAuthorization($address);
                        $this->AddressRepo->delete($address);
                    } catch (AccessDeniedHttpException $e) {
                        continue;
                    }
                }
            }
        }
        return response(null, 204);
    }

    public function validateAddress(ValidateAddressRequest $request)
    {
        $coins = config('coin');
        $values = $request->validated();
        $res = $this->WalletService->getAddressValidation($values['coin'], urldecode($values['payload']));

        $result = [
            'coin' => $values['coin'],
            'address' => data_get($res, 'address'),
            'tx_count' => data_get($res, 'tx_count'),
        ];
        if (data_get($res, 'amount') !== null) {
            $result['amount'] = data_get($res, 'amount');
        }
        if (data_get($res, 'checksum') !== null) {
            $result['checksum'] = data_get($res, 'checksum');
        }
        return $result;
    }

    protected function checkAuthorization(Address $address)
    {
        if (data_get($address->user, 'id') !== \Auth::id()) {
            throw new AccessDeniedHttpException;
        }
        return true;
    }
}
