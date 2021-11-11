<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Dec\Dec;
use Symfony\Component\HttpKernel\Exception\{
    NotFoundHttpException,
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\{
    SecurityCodeTrait,
    ListQueryTrait,
};
use App\Exceptions\{
    Auth\WrongSecurityCodeError,
    Auth\WrongTFACodeError,
    Verification\ExpiredVerificationError,
    Verification\WrongCodeError,
    Core\BadRequestError,
    DuplicateRecordError,
    VendorException,
    WithdrawLimitationError,
    WithdrawalStatusError,
    WrongAddressFormatError,
    UserFeatureLockError,
};
use App\Http\Requests\{
    PreviewWithdrawalRequest,
    CreateWithdrawalRequest,
    DuplicateWithdrawalRequest,
    WithdrawalListRequest,
};
use App\Http\Resources\{
    VerificationResource,
    WithdrawalResource,
};
use App\Notifications\{
    WithdrawalVerification,
};
use App\Repos\Interfaces\{
    UserRepo,
    LimitationRepo,
    VerificationRepo,
    WithdrawalRepo,
};
use App\Services\{
    AccountServiceInterface,
    WalletServiceInterface,
    TwoFactorAuthServiceInterface,
};
use App\Models\{
    Limitation,
    Verification,
    UserLog,
    UserLock,
};

class WithdrawalController extends Controller
{
    use SecurityCodeTrait, ListQueryTrait;

    public function __construct(
        UserRepo $UserRepo,
        LimitationRepo $LimitationRepo,
        VerificationRepo $VerificationRepo,
        WithdrawalRepo $WithdrawalRepo,
        AccountServiceInterface $AccountService,
        WalletServiceInterface $WalletService,
        TwoFactorAuthServiceInterface $TwoFactorAuthService
    ) {
        parent::__construct();
        $this->coins = config('coin');
        $this->UserRepo = $UserRepo;
        $this->LimitationRepo = $LimitationRepo;
        $this->VerificationRepo = $VerificationRepo;
        $this->WithdrawalRepo = $WithdrawalRepo;
        $this->AccountService = $AccountService;
        $this->WalletService = $WalletService;
        $this->TwoFactorAuthService = $TwoFactorAuthService;
        $this->middleware(
            'auth:api',
            ['only' => [
                'show',
                'preview',
                'create',
            ]]
        );
        $this->middleware(
            'userlock',
            ['only' => [
                'show',
                'preview',
                'create',
            ]]
        );
    }

    public function getWithdrawals(WithdrawalListRequest $request)
    {
        $values = $request->validated();
        $result = $this->WithdrawalRepo->getUserWithdrawals(
            auth()->user(),
            data_get($values, 'coin'),
            $this->inputDateTime('start'),
            $this->inputDateTime('end'),
            $this->inputLimit(),
            $this->inputOffset()
        );

        return $this->paginationResponse(
            WithdrawalResource::collection($result['data']),
            $result['filtered'],
            $result['total']
        );
    }

    public function show($id)
    {
        $user = auth()->user();
        $withdrawal = $this->WithdrawalRepo->findOrFail($id);
        if (!$withdrawal->user->is($user)) {
            throw new AccessDeniedHttpException;
        }

        return new WithdrawalResource($withdrawal);
    }

    public function preview(PreviewWithdrawalRequest $request)
    {
        $values = $request->validated();
        $user = auth()->user();
        $result = $this->AccountService
            ->calcWithdrawal(
                $user,
                data_get($values, 'coin'),
                data_get($values, 'amount'),
                false
            );
        return [
            'coin' => data_get($result, 'coin'),
            'amount' => data_get($result, 'amount'),
            'fee' => data_get($result, 'fee'),
            'out_of_limits' => data_get($result, 'out_of_limits'),
            'balance_insufficient' => data_get($result, 'balance_insufficient'),
        ];
    }

    public function duplicateCheck(DuplicateWithdrawalRequest $request)
    {
        $values = $request->validated();
        $user = auth()->user();
        $withdrawal = $this->WithdrawalRepo->getUserLatest($user);

        $values['amount'] = Dec::create($values['amount'])->floor($this->coins[$values['coin']]['decimal']);

        if ($withdrawal) {
            if (
                $values['coin'] === $withdrawal->coin and
                $values['address'] === $withdrawal->address and
                data_get($values, 'tag') === $withdrawal->tag and
                Dec::eq($values['amount'], $withdrawal->amount) and
                Carbon::now() < $withdrawal->created_at->addDay()
            ) {
                return ['duplicate' => true];
            }
        }
        return ['duplicate' => false];
    }

    public function create(CreateWithdrawalRequest $request)
    {
        $value = $request->validated();
        $user = auth()->user();

        # check security_code
        $this->checkSecurityCode($user, $value['security_code']);

        # check 2fa
        if (data_get($value, 'code')) {
            if (!$this->TwoFactorAuthService->verify($user, data_get($value, 'code'))) {
                throw new WrongTFACodeError;
            }
        }

        # check user lock
        if ($this->UserRepo->checkUserFeatureLock($user, UserLock::WITHDRAWAL)) {
            throw new UserFeatureLockError;
        }

        # address validation
        $this->WalletService->getAddressValidation($value['coin'], $value['address']);

        # withdraw limitation check
        if (!$this->LimitationRepo->checkLimitation(
            $user,
            Limitation::TYPE_WITHDRAWAL,
            data_get($value, 'coin'),
            data_get($value, 'amount')
        )) {
            throw new WithdrawLimitationError;
        }

        $withdrawal = $this->AccountService->createWithdrawal(
            $user,
            data_get($value, 'coin'),
            data_get($value, 'amount'),
            data_get($value, 'address'),
            data_get($value, 'tag'),
            data_get($value, 'message')
        );
        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_WITHDRAWAL_CONFIRMATION,
            'data' => $user->email,
        ], $withdrawal);
        $this->VerificationRepo->notify($verification, $user, new WithdrawalVerification($verification, $withdrawal));
        return new WithdrawalResource($withdrawal);
    }

    public function confirm(string $id, string $code)
    {
        try {
            if (!$verification = $this->VerificationRepo->find($id)) {
                throw new BadRequestError;
            }

            $withdrawal = $verification->verificable;

            # Check if withdrawal already confirmed
            if ($withdrawal->is_confirmed) {
                throw new DuplicateRecordError;
            }
            # Check withdrawal expiration
            if (
                $withdrawal->is_expired or
                $withdrawal->is_canceled
            ) {
                throw new WithdrawalStatusError;
            }

            $this->VerificationRepo->verify(
                $verification,
                $code,
                null,
                Verification::TYPE_WITHDRAWAL_CONFIRMATION
            );

            # Set withdrawal's confirmed_at
            $this->WithdrawalRepo->confirm($withdrawal);

        } catch (
            ExpiredVerificationError |
            WithdrawalStatusError $e
        ) {
            return redirect(url('/withdrawal-confirmation?status=0&message=expired'));
        } catch (
            BadRequestError |
            WrongCodeError $e
        ) {
            return redirect(url('/'));
        } catch (DuplicateRecordError $e) {
            return redirect(url('/withdrawal-confirmation?status=1'));
        } catch (\Throwable $e) {
            Log::alert("WithdrawalController/confirm. Withdrawal {$id} vendorException or UnknownException. {$e->getMessage()}");
        }
        return redirect(url('/withdrawal-confirmation?status=1'));
    }

    public function withdrawalCallback(Request $request, string $id)
    {
        Log::info("Wallet withdrawCallback request", $request->all());
        # verify signature
        $this->WalletService->verifyRequest($request);

        $withdrawal = $this->WithdrawalRepo->findOrFail($id);
        $values = $request->all();

        try {
            $this->AccountService->handleWithdrawalCallback($withdrawal, $values);
        } catch (DuplicateRecordError $e) {
            Log::error("withdrawalCallback. Duplicate withdrawal {$withdrawal->id} callback received.");
        } catch (VendorException $e) {
            Log::alert("withdrawalCallback. VendorException: ". $e->getMessage());
            return response(null, 400);
        }
        return response(null, 200);
    }

    public function manualWithdrawalCallback(Request $request)
    {
        Log::info("Wallet manualWithdrawalCallback request", $request->all());
        # verify signature
        $this->WalletService->verifyRequest($request);

        try {
            $log = $this->AccountService->handleManualWithdrawalCallback($request->all());
            Log::alert("Wallet Manual Withdrawal", $log->toArray());
        } catch (DuplicateRecordError $e) {
        } catch (NotFoundHttpException $e) {
        } catch (VendorException $e) {
        }
        return response(null, 200);
    }
}
