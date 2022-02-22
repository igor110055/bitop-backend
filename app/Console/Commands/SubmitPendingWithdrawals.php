<?php

namespace App\Console\Commands;

use Dec\Dec;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Exceptions\WithdrawalStatusError;
use App\Repos\Interfaces\WithdrawalRepo;
use App\Services\AccountServiceInterface;

class SubmitPendingWithdrawals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'submit:pending-withdrawals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Submit pending (confirmed & ex-submitted failed) withdrawals.";

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
        $withdrawals = $this->WithdrawalRepo->getAllPending();

        foreach ($withdrawals as $w) {
            try {
                # Submit withdraw request
                $this->line("SubmitPendingWithdrawals. Submit {$w->id}.");
                Log::info("SubmitPendingWithdrawals. Submit {$w->id}.");
                $this->AccountService->submitWithdrawal($w);
            } catch (\Throwable $e) {
                $this->error("SubmitPendingWithdrawals, withdrawal {$w->id} submit failed. {$e->getMessage()}");
                Log::alert("SubmitPendingWithdrawals, withdrawal {$w->id} submit failed. {$e->getMessage()}");
            }
        }
    }
}
