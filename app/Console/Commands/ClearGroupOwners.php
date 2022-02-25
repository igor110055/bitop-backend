<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearGroupOwners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:group-owners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set all groups' owner to null";

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
        $affacted = DB::table('groups')
            ->update(['user_id' => null]);
        $this->info("{$affacted} groups updated.");
    }
}
