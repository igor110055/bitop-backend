<?php

namespace App\Http\Controllers\Api;

use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
};
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\{
    SecurityCodeTrait,
    ListQueryTrait,
};
use App\Http\Requests\{
    TransferRequest,
    TransferListRequest,
};
use App\Models\{
    Transfer,
    Verification,
    UserLock,
};
use App\Notifications\{
    TransferVerification,
    TransferNotification,
};
use App\Exceptions\{
    Core\BadRequestError,
    Auth\WrongSecurityCodeError,
    Auth\WrongTFACodeError,
    Verification\WrongCodeError,
    Verification\ExpiredVerificationError,
    DuplicateRecordError,
    TransferStatusError,
    DstUserUnauthError,
    UserFeatureLockError,
};
use App\Repos\Interfaces\{
    UserRepo,
    TransferRepo,
    VerificationRepo,
};
use App\Services\{
    TransferServiceInterface,
    TwoFactorAuthServiceInterface,
};
use App\Http\Resources\{
    TransferResource,
};
use App\Jobs\Push\TransferNotification as PushTransferNotification;

class TransferController extends Controller
{
    use SecurityCodeTrait, ListQueryTrait;

    public function __construct(
        UserRepo $ur,
        VerificationRepo $vr,
        TransferRepo $tr,
        TransferServiceInterface $ts,
        TwoFactorAuthServiceInterface $tfas
    ) {
        parent::__construct();
        $this->UserRepo = $ur;
        $this->VerificationRepo = $vr;
        $this->TransferRepo = $tr;
        $this->TransferService = $ts;
        $this->TwoFactorAuthService = $tfas;
        $this->middleware('real_name.check', ['only' => ['create']]);
        $this->middleware(
            'auth:api',
            ['only' => ['show', 'create', 'getTransfers']]
        );
        $this->middleware(
            'userlock',
            ['only' => ['show','create', 'getTransfers']]
        );
    }

    public function getTransfers(TransferListRequest $request)
    {
        $values = $request->validated();
        if ($search_user = data_get($values, 'user_id')) {
            $search_user = $this->UserRepo->findOrFail($search_user);
        }

        $result = $this->TransferRepo->getUserTransfers(
            auth()->user(),
            data_get($values, 'coin'),
            data_get($values, 'side'),
            $search_user,
            $this->inputDateTime('start'),
            $this->inputDateTime('end'),
            $this->inputLimit(),
            $this->inputOffset()
        );

        return $this->paginationResponse(
            TransferResource::collection($result['data']),
            $result['filtered'],
            $result['total']
        );
    }

    public function show(string $id)
    {
        $transfer = $this->TransferRepo->findOrFail($id);
        $this->checkAuthorization($transfer);
        return new TransferResource($transfer);
    }

    public function create(TransferRequest $request)
    {
        $values = $request->validated();
        $user = auth()->user();

        # check security_code
        $this->checkSecurityCode($user, $values['security_code']);

        # check 2fa
        if (data_get($values, 'code')) {
            if (!$this->TwoFactorAuthService->verify($user, data_get($values, 'code'))) {
                throw new WrongTFACodeError;
            }
        }

        # check user lock
        if ($this->UserRepo->checkUserFeatureLock($user, UserLock::TRANSFER)) {
            throw new UserFeatureLockError;
        }

        # check dst_user auth status
        $dst_user = $this->UserRepo->findOrFail($values['dst_user_id']);
        if (!$dst_user->is_verified) {
            throw new DstUserUnauthError;
        }

        # create transfer
        $transfer = $this->TransferService->make(
            $user,
            $dst_user,
            $values['coin'],
            $values['amount'],
            data_get($values, 'message'),
            data_get($values, 'memo')
        );

        $verification = $this->VerificationRepo->getOrCreate([
            'type' => Verification::TYPE_TRANSFER_CONFIRMATION,
            'data' => $user->email,
        ], $transfer);
        $this->VerificationRepo->notify($verification, $user, new TransferVerification($verification, $transfer));
        return new TransferResource($transfer);
    }

    public function confirm(string $id, string $code)
    {
        try {
            if (!$verification = $this->VerificationRepo->find($id)) {
                throw new BadRequestError;
            }
            $transfer = $verification->verificable;
            # Check if transfer already confirmed
            if ($transfer->is_confirmed) {
                throw new DuplicateRecordError;
            }
            # Check transfer expiration
            if ($transfer->is_expired or $transfer->is_canceled) {
                throw new TransferStatusError;
            }

            $this->VerificationRepo->verify(
                $verification,
                $code,
                null,
                Verification::TYPE_TRANSFER_CONFIRMATION
            );

            $this->TransferService->confirm($transfer);

        } catch (ExpiredVerificationError | TransferStatusError $e) {
            return redirect(url('/transfer-confirmation?status=0&message=expired'));
        } catch (BadRequestError | WrongCodeError $e) {
            return redirect(url('/'));
        } catch (DuplicateRecordError $e) {
            return redirect(url('/transfer-confirmation?status=1'));
        } catch (\Throwable $e) {
            Log::alert("TransferController/confirm. UnknownException. {$e->getMessage()}");
            return redirect(url('/transfer-confirmation?status=0&message=system-error'));
        }
        $transfer->dst_user->notify(new TransferNotification($transfer));
        PushTransferNotification::dispatch($transfer->dst_user, $transfer)->onQueue(config('services.push_notification.queue_name'));
        return redirect(url('/transfer-confirmation?status=1'));
    }

    protected function checkAuthorization(Transfer $transfer)
    {
        if (data_get($transfer->src_user, 'id') !== \Auth::id() and
            data_get($transfer->dst_user, 'id') !== \Auth::id()
        ) {
            throw new AccessDeniedHttpException;
        }
        return true;
    }
}
