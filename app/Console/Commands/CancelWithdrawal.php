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

class CancelWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancel:withdrawal {withdrawal_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Cancel an un-SubmittedConfirmed and un-canceled withdrawal.";

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
        $withdrawal_id = $this->argument('withdrawal_id');
        $withdrawal = $this->WithdrawalRepo->findOrFail($withdrawal_id);
        if ($withdrawal->is_canceled or $withdrawal->is_submitted_confirmed) {
            throw new WithdrawalStatusError('Withdrawal is subitted-confirmed or canceld');
        }
        $this->AccountService->cancelWithdrawal($withdrawal, Withdrawal::EXPIRED);
        $this->line("Withdrawal {$withdrawal->id} canceled.");
        Log::info("CancelWithdrawal, Withdrawal {$withdrawal->id} canceled.");
    }
}
