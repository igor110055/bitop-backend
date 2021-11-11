<?php

namespace App\Console\Commands;

use Dec\Dec;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Exceptions\WithdrawalStatusError;
use App\Repos\Interfaces\WithdrawalRepo;
use App\Services\WalletServiceInterface;

class ResubmitWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resubmit:withdrawal {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Resubmit submitted-confirmed but unnotified withdrawal (blockchain-failed). Will not update balance.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        WithdrawalRepo $WithdrawalRepo,
        WalletServiceInterface $WalletService
    ) {
        $this->WithdrawalRepo = $WithdrawalRepo;
        $this->WalletService= $WalletService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->argument('id');
        $withdrawal = $this->WithdrawalRepo->findOrFail($id);
        if (!$withdrawal->is_submitted_confirmed or
            $withdrawal->is_notified or
            $withdrawal->is_canceled
        ) {
            $this->error('Wrong status');
            exit;
        }

        list($id, $user, $coin, $amount, $address, $tag, $fee, $callback) = [
            $withdrawal->id,
            $withdrawal->user,
            $withdrawal->coin,
            $withdrawal->amount,
            $withdrawal->address,
            $withdrawal->tag,
            $withdrawal->fee,
            $withdrawal->callback,
        ];

        $response = $this->WalletService->withdrawal(
            $coin,
            $address,
            $tag,
            $amount,
            $callback,
            $id,  # client_id
            true  # is_full_payment
        );
        $this->line(json_encode($response));
        Log::alert("ResubmitWithdrawal. Withdrawal {$id}", $response);
    }
}
