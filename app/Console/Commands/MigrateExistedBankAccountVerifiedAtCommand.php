<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;
use App\Models\{
    BankAccount,
};

class MigrateExistedBankAccountVerifiedAtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:bank-account-verified-at';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existed bankAccounts verified_at set as currenct time.';

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
        $affacted = BankAccount::whereNull('verified_at')
            ->whereNull('deleted_at')
            ->update(['verified_at' => Carbon::now()->format('Uv')]);

        $this->line("MigrateExjstedBankAccountVerifiedAtCommand. {$affacted} row updated.");
    }
}
