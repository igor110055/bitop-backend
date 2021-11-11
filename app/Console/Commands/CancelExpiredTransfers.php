<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Exceptions\TransferStatusError;
use App\Repos\Interfaces\TransferRepo;
use App\Services\TransferServiceInterface;
use App\Models\SystemAction;

class CancelExpiredTransfers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancel:expired-transfers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Cancel expired transfer and unlock src_user's balance";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $TransferRepo = app()->make(TransferRepo::class);
        $TransferService = app()->make(TransferServiceInterface::class);
        $transfers = $TransferRepo->getExpiredTransfers();

        foreach ($transfers as $transfer) {
            try {
                $TransferService->cancel($transfer, SystemAction::class);
                $this->line("CancelExpiredTransfers, {$transfer->id} canceled.");
                Log::info("CancelExpiredTransfers, {$transfer->id} canceled.");
            } catch (TransferStatusError $e) {
                $this->error("CancelExpiredTransfers, transfer {$transfer->id} status error.", $transfer->toArray());
                Log::alert("CancelExpiredTransfers, transfer {$transfer->id} status error.", $transfer->toArray());
            } catch (\Throwable $e) {
                $this->error("CancelExpiredTransfers, transfer {$transfer->id} unkonwn error. {$e->getMessage()}");
                Log::alert("CancelExpiredTransfers, transfer {$transfer->id} unkonwn error. {$e->getMessage()}");

            }
        }
    }
}
