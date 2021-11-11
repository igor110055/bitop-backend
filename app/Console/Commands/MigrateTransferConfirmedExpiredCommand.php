<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transfer;

class MigrateTransferConfirmedExpiredCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:transfer-confirmed-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Fill up existed transfer 'confirmed_at' and 'expired_at'";

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
        $transfers = Transfer::whereNull('expired_at')
            ->get();
        dump("Fill up 'confirmed_at' and 'expired_at' in old transfers");
        foreach ($transfers as $t) {
            $t->update([
                'confirmed_at' => $t->updated_at,
                'expired_at' => $t->updated_at
            ]);
            dump("Transfer id: {$t->id}");
        }
    }
}
