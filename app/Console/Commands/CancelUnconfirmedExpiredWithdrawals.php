<?php

namespace App\Console\Commands;

use Dec\Dec;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Withdrawal;
use App\Exceptions\WithdrawalStatusError;
use App\Repos\Interfaces\WithdrawalRepo;
use App\Services\AccountServiceInterface;

class CancelUnconfirmedExpiredWithdrawals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancel:unconfirmed-expired-withdrawals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Cancel Unconfirmed and expired withdrawals and unlock users' balance.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        WithdrawalRepo $WithdrawalRepo,
        AccountServiceInterface $AccountService
    ) {
        $this->WithdrawalRepo = $WithdrawalRepo;
        $this->AccountService= $AccountService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $withdrawals = $this->WithdrawalRepo->getAllUnconfirmedExpired();

        foreach ($withdrawals as $w) {
            try {
                $this->AccountService->cancelWithdrawal($w, Withdrawal::EXPIRED);
                $this->line("CancelUnconfirmedExpiredWithdrawals, {$w->id} canceled.");
                Log::info("CancelUnconfirmedExpiredWithdrawals, {$w->id} canceled.");
            } catch (WithdrawalStatusError $e) {
                $this->error("CancelUnconfirmedExpiredWithdrawals, withdrawal {$w->id} status error.", $w->toArray());
                Log::alert("CancelUnconfirmedExpiredWithdrawals, withdrawal {$w->id} status error.", $w->toArray());
            } catch (\Throwable $e) {
                $this->error("CancelUnconfirmedExpiredWithdrawals, withdrawal {$w->id} unkonwn error. {$e->getMessage()}");
                Log::alert("CancelUnconfirmedExpiredWithdrawals, withdrawal {$w->id} unkonwn error. {$e->getMessage()}");
            }
        }
    }
}
