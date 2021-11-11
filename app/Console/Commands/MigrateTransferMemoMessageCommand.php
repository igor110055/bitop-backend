<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{
    Transfer,
    Transaction,
};

class MigrateTransferMemoMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:transfer-memo-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate transaction message to transfer memo and message';

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
        $update = [];
        $transactions = Transaction::whereHasMorph('transactable', [Transfer::class])
            ->whereNotNull('message')
            ->get();
        foreach ($transactions as $t) {
            if ($t->type === Transaction::TYPE_TRANSFER_IN) {
                $update[$t->transactable_id]['message'] = $t->message;
            } elseif ($t->type === Transaction::TYPE_TRANSFER_OUT) {
                $update[$t->transactable_id]['memo'] = $t->message;
            }
        }
        foreach ($update as $key => $values) {
            if ($transfer = Transfer::where('id', $key)
                ->update($values) === 1) {
                dump("Added memo and message to Transfer {$key}");
            }
        }
    }
}
