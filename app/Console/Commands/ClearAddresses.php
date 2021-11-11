<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearAddresses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:addresses {coin?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all address, only to be excuted when we change our wallet account or wallet service has major update.';

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
        $coin = $this->argument('coin');
        $affected = DB::table('accounts')
            ->when($coin, function ($query, $coin) {
                return $query->where('coin', $coin);
            })
            ->update([
                'address' => null,
                'tag' => null,
            ]);
        $this->line("{$affected} rows updated");
        Log::alert("Account address has been cleared.");
    }
}
