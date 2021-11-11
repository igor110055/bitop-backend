<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Models\Account;
use App\Services\AccountServiceInterface;

class UpdateExistingAddresses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:addresses {coin?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For accounts which already have address, call createAddress() and update existing address.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AccountServiceInterface $AccountService)
    {
        parent::__construct();
        $this->AccountService = $AccountService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $coin = $this->argument('coin');
        $accounts = Account::whereNotNull('address')
            ->when($coin, function ($query, $coin) {
                return $query->where('coin', $coin);
            })
            ->get();
        foreach ($accounts as $account) {
            $account->address = null;
            $account->save();
            $result = $this->AccountService->getWalletAddress($account->user, $account->coin);
        }
        $affected = $accounts->count();
        $this->line("{$affected} rows updated");
        Log::alert("Account address has been updated.");
    }
}
