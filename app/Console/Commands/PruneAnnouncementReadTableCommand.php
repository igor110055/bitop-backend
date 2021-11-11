<?php

namespace App\Console\Commands;

use DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Repos\Interfaces\SystemActionRepo;
use App\Models\SystemAction;

class PruneAnnouncementReadTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prune:announcement-read-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'prune read data three months before';

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
        $rows = DB::table('announcement_reads')->where('created_at', '<', Carbon::now()->subMonths(3))->delete();
        app()->make(SystemActionRepo::class)->create([
            'type' => SystemAction::TYPE_PRUNE_ANNOUNCEMENT_READ_TABLE,
            'description' => 'System prune announcement_read table',
        ]);
        if ($rows > 0) {
            dump("announcement_reads table: $rows deleted");
        } else {
            dump("announcement_reads table: nothing to prune");
        }
    }
}
