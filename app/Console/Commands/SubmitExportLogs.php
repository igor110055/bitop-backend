<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Repos\Interfaces\ExportLogRepo;
use App\Services\{
    ExportServiceInterface,
};

class SubmitExportLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'submit:export_logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Submit unsubmitted/failed export_logs.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ExportLogRepo $ExportLogRepo,
        ExportServiceInterface $ExportService
    ) {
        $this->ExportLogRepo = $ExportLogRepo;
        $this->ExportService = $ExportService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $link = config('services.export_log.link');
        if (empty($link)) {
            return;
        }

        $export_logs = $this->ExportLogRepo->getAllPending();

        foreach ($export_logs as $log) {
            try {
                $this->ExportService->submit($log);
            } catch (\Throwable $e) {
                # Do nothinng
            }
        }
    }
}
