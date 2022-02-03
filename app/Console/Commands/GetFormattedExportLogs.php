<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Repos\Interfaces\ExportLogRepo;
use App\Services\{
    ExportServiceInterface,
};

class GetFormattedExportLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:export_logs {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Get formatted json of export_log.";

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
        $id = $this->argument('id');
        $export_log = $this->ExportLogRepo->findOrFail($id);
        $formatted = $this->ExportService->formatData($export_log);
        $this->info($formatted);
    }
}
