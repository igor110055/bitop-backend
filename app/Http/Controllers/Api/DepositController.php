<?php

namespace App\Http\Controllers\Api;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ListQueryTrait;
use App\Exceptions\{
    Core\BadRequestError,
    DuplicateRecordError,
    ServiceUnavailableError,
    VendorException,
};
use App\Http\Requests\DepositListRequest;
use App\Http\Resources\{
    DepositResource,
};
use App\Models\{
    Config,
};
use App\Repos\Interfaces\{
    ConfigRepo,
    DepositRepo,
    UserRepo,
};
use App\Services\{
    AccountServiceInterface,
    WalletServiceInterface,
};
use App\Notifications\{
    DepositNotification,
};
use App\Jobs\PushNotification\DepositNotification as PushDepositNotification;

class DepositController extends Controller
{
    use ListQueryTrait;

    public function __construct(
        ConfigRepo $ConfigRepo,
        DepositRepo $DepositRepo,
        UserRepo $UserRepo,
        AccountServiceInterface $AccountService,
        WalletServiceInterface $WalletService
    ) {
        parent::__construct();
        $this->coins = config('coin');
        $this->ConfigRepo = $ConfigRepo;
        $this->DepositRepo = $DepositRepo;
        $this->UserRepo = $UserRepo;
        $this->AccountService = $AccountService;
        $this->WalletService = $WalletService;
        $this->middleware(
            'auth:api',
            ['only' => [
                'show',
                'getAddress',
            ]]
        );
    }

    public function getDeposits(DepositListRequest $request)
    {
        $values = $request->validated();

        $result = $this->DepositRepo->getUserDeposits(
            auth()->user(),
            data_get($values, 'coin'),
            $this->inputDateTime('start'),
            $this->inputDateTime('end'),
            $this->inputLimit(),
            $this->inputOffset()
        );

        return $this->paginationResponse(
            DepositResource::collection($result['data']),
            $result['filtered'],
            $result['total']
        );
    }

    public function show(string $id)
    {
        $user = auth()->user();
        $deposit = $this->DepositRepo->findOrFail($id);
        if (!$deposit->user->is($user)) {
            throw new AccessDeniedHttpException;
        }

        return new DepositResource($deposit);
    }

    public function getAddress(Request $request)
    {
        $coin = $request->input('coin');
        $coin_map = config('services.wallet.coin_map');
        $user = auth()->user();
        if (is_null($coin) or !in_array($coin, array_keys(hide_beta_coins($user, $this->coins)))) {
            throw new BadRequestError;
        }

        # Check wallet status
        if ($this->ConfigRepo->get(Config::ATTRIBUTE_WALLET, 'deactivated') === true) {
            throw new ServiceUnavailableError;
        } else {
            $wallet_res = $this->WalletService->getCoinInfo();
            if (data_get($wallet_res, $coin_map[$coin]) !== 'active') {
                throw new ServiceUnavailableError;
            }
        }

        $result = $this->AccountService->getWalletAddress($user, $coin);
        return [
            'coin' => $result['coin'],
            'address' => $result['address'],
            'tag' => $result['tag'],
        ];
    }

    # General Deposit Callback
    public function depositCallback(Request $request, string $id)
    {
        Log::info('Wallet depositCallback request', $request->all());

        # verify signature
        $this->WalletService->verifyRequest($request);

        $user = $this->UserRepo->findOrFail($id);

        $values = $request->all();

        # Callback parameter existence check
        $this->WalletService->checkDepositCallbackParameter($values);

        try {
            $deposit = $this->AccountService->createDeposit($user, $values);
        } catch (DuplicateRecordError $e) {
            return response(null, 200);
        } catch (VendorException $e) {
            return response(null, 200);
        }
        $deposit->user->notify(new DepositNotification($deposit));
        PushDepositNotification::dispatch($deposit->user, $deposit)->onQueue(config('services.push_notification.queue_name'));
        return response(null, 200);
    }

    # Maunal Deposit Callback
    # Manual deposit to MAIN account; no deposit record created
    public function manualDepositCallback(Request $request)
    {
        Log::info('Wallet manualDepositCallback request', $request->all());

        # verify signature
        $this->WalletService->verifyRequest($request);
        $values = $request->all();

        # Callback parameter existence check
        $this->WalletService->checkDepositCallbackParameter($values);

        try {
            $log = $this->AccountService->manualDeposit($values);
            Log::alert("Wallet Manual Deposit", $log->toArray());
        } catch (DuplicateRecordError $e) {
        } catch (VendorException $e) {
        }
        return response(null, 200);
    }

    public function payinCallback(Request $request)
    {
        Log::info('Wallet payinCallback request', $request->all());

        # verify signature
        $this->WalletService->verifyRequest($request);
        $values = $request->all();

        # Callback parameter existence check
        $this->WalletService->checkWalletInternalCallbackParameter($values);

        try {
            $this->AccountService->handlePayinCallback($values);
        } catch (DuplicateRecordError $e) {
        } catch (VendorException $e) {
        }
        return response(null, 200);
    }

    public function payoutCallback(Request $request)
    {
        Log::info('Wallet payoutCallback request', $request->all());

        # verify signature
        $this->WalletService->verifyRequest($request);
        $values = $request->all();

        # Callback parameter existence check
        $this->WalletService->checkWalletInternalCallbackParameter($values);

        try {
            $this->AccountService->handlePayoutCallback($values);
        } catch (DuplicateRecordError $e) {
        } catch (VendorException $e) {
        }
        return response(null, 200);
    }

    public function approvementCallback(Request $request)
    {
        Log::info('Wallet approvementCallback request', $request->all());

        # verify signature
        $this->WalletService->verifyRequest($request);
        $values = $request->all();

        # Callback parameter existence check
        $this->WalletService->checkWalletInternalCallbackParameter($values);

        try {
            $this->AccountService->handleApprovementCallback($values);
        } catch (DuplicateRecordError $e) {
        } catch (VendorException $e) {
        }
        return response(null, 200);
    }
}
