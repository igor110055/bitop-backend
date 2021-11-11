<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;

class MigrateTransactionTransferMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:transaction-transfer-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set 'transfer-in' and 'transfer-out' transactions 'message' to null";

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
        $transactions = Transaction::where(function ($query) {
                $query->where('type', 'transfer-out')
                    ->orWhere('type', 'transfer-in');
            })
            ->whereNotNull('message')
            ->get();
        dump("Clear 'transfer-in' and 'transfer-out' transactions messages");
        foreach ($transactions as $t) {
            $t->update(['message' => null]);
            dump("Transaction id: {$t->id}");
        }
    }
}
