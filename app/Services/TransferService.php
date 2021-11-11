<?php

namespace App\Services;

use DB;
use Dec\Dec;
use Carbon\Carbon;
use App\Exceptions\{
    Core\BadRequestError,
};
use App\Models\{
    User,
    Transaction,
    Transfer,
    SystemAction,
};
use App\Repos\Interfaces\{
    TransferRepo,
    AccountRepo,
    SystemActionRepo,
};
use App\Services\{
    AccountServiceInterface,
};

class TransferService implements TransferServiceInterface
{
    public function __construct(
        TransferRepo $TransferRepo,
        AccountRepo $AccountRepo,
        AccountServiceInterface $AccountService
    ) {
        $this->TransferRepo = $TransferRepo;
        $this->AccountRepo = $AccountRepo;
        $this->AccountService = $AccountService;
    }

    protected function normalizeAmount(string $amount, string $coin)
    {
        $amount = Dec::create($amount)->floor(config("coin.{$coin}.decimal"));
        if (!$amount->isPositive()) {
            throw new BadRequestError;
        }
        return (string) $amount;
    }

    public function make(
        User $src_user,
        User $dst_user,
        string $coin,
        string $amount,
        string $message = null,
        string $memo = null
    ) {
        $src_account = $this->AccountRepo->findByUserCoinOrCreate($src_user, $coin);
        $dst_account = $this->AccountRepo->findByUserCoinOrCreate($dst_user, $coin);
        $amount = $this->normalizeAmount($amount, $coin);

        return DB::transaction(function () use (
            $src_user,
            $dst_user,
            $src_account,
            $dst_account,
            $coin,
            $amount,
            $message,
            $memo
        ) {
            # create transfer
            $transfer = $this->TransferRepo->create([
                'src_user_id' => $src_user->id,
                'dst_user_id' => $dst_user->id,
                'src_account_id' => $src_account->id,
                'dst_account_id' => $dst_account->id,
                'coin' => $coin,
                'amount' => $amount,
                'message' => $message,
                'memo' => $memo,
                'expired_at' => Carbon::now()->addMinute(config('core.transfer.timeout')),
            ]);

            # lock amount
            $this->AccountService->lock(
                $src_user,
                $coin,
                $amount,
                Transaction::TYPE_TRANSFER_LOCK,
                $transfer
            );

            return $transfer;
        });
    }

    public function confirm(Transfer $transfer)
    {
        $src_account = $this->AccountRepo->findByUserCoinOrCreate(
            $transfer->src_user,
            $transfer->coin
        );

        DB::transaction(function () use ($transfer, $src_account) {
            $this->AccountService->unlock(
                $transfer->src_user,
                $transfer->coin,
                $transfer->amount,
                Transaction::TYPE_TRANSFER_UNLOCK,
                $transfer
            );
            # withdraw from src_user's account and create transaction
            $this->AccountService->withdraw(
                $transfer->src_user,
                $transfer->coin,
                $transfer->amount,
                Transaction::TYPE_TRANSFER_OUT,
                $transfer,
                $transfer->memo
            );
            # deposit to dst_user's account and create transaction
            $this->AccountService->deposit(
                $transfer->dst_user,
                $transfer->coin,
                $transfer->amount,
                Transaction::TYPE_TRANSFER_IN,
                $src_account->unit_price, # unit_price
                $transfer,
                $transfer->message
            );
            $this->TransferRepo->confirm($transfer);
        });
    }

    public function cancel(Transfer $transfer, string $role = SystemAction::class)
    {
        $SystemActionRepo = app()->make(SystemActionRepo::class);
        DB::transaction(function () use ($transfer, $SystemActionRepo, $role) {
            $this->AccountService->unlock(
                $transfer->src_user,
                $transfer->coin,
                $transfer->amount,
                Transaction::TYPE_TRANSFER_CANCELED,
                $transfer
            );
            $this->TransferRepo->cancel($transfer);
            if ($role === SystemAction::class) {
                $SystemActionRepo->createByApplicable($transfer, [
                    'type' => SystemAction::TYPE_CANCEL_TRANSFER,
                    'description' => 'System cancel this transfer due to expiration',
                ]);
            }
        });
    }
}
