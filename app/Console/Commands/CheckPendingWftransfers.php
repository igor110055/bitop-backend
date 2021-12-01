<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Repos\Interfaces\WftransferRepo;
use App\Services\{
    OrderServiceInterface,
    WfpayServiceInterface,
};

class CheckPendingWftransfers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:wftransfers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check pending wftransfers' remote status.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        WftransferRepo $WftransferRepo,
        OrderServiceInterface $OrderService,
        WfpayServiceInterface $WfpayService
    ) {
        $this->WftransferRepo = $WftransferRepo;
        $this->OrderService = $OrderService;
        $this->WfpayService = $WfpayService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $wftransfers = $this->WftransferRepo->getAllPending();

        foreach ($wftransfers as $wftransfer) {
            try {
                $remote = $this->WfpayService->getTransfer($wftransfer->id);
                $this->line($wftransfer->id);
                $this->OrderService->updateWftransferAndOrder($wftransfer->id, $remote, false);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $this->error("CheckPendingWftransfers, get remote order {$wftransfer->id} failed. {$msg}");
                Log::critical("CheckPendingWftransfers, get remote order {$wftransfer->id} failed. {$msg}");
                $msg = json_decode($msg, true);
                if (data_get($msg, 'error_key') === 'order_not_found') {
                    $this->WftransferRepo->update($wftransfer, ['closed_at' => millitime()]);
                }
            }
        }
    }
}
