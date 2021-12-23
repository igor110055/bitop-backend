<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Repos\Interfaces\WfpaymentRepo;
use App\Services\{
    OrderServiceInterface,
    WfpayServiceInterface,
};

class CheckPendingWfpayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:wfpayments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check pending wfpayments' remote status.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        WfpaymentRepo $WfpaymentRepo,
        OrderServiceInterface $OrderService,
        WfpayServiceInterface $WfpayService
    ) {
        $this->WfpaymentRepo = $WfpaymentRepo;
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
        $wfpayments = $this->WfpaymentRepo->getAllPending();

        foreach ($wfpayments as $wfpayment) {
            try {
                $remote = $this->WfpayService->getOrder($wfpayment);
                $this->line($wfpayment->id);
                $this->OrderService->updateWfpaymentAndOrder($wfpayment->id, $remote, false);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $this->error("CheckPendingWfpayments, get remote order {$wfpayment->id} failed. {$msg}");
                Log::critical("CheckPendingWfpayments, get remote order {$wfpayment->id} failed. {$msg}");
                $msg = json_decode($msg, true);
                if (data_get($msg, 'error_key') === 'order_not_found') {
                    $this->WfpaymentRepo->update($wfpayment, ['closed_at' => millitime()]);
                }
            }
        }
    }
}
