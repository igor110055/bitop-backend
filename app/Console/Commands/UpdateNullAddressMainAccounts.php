<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Models\Account;
use App\Repos\Interfaces\AccountRepo;
use App\Services\AccountServiceInterface;

class UpdateNullAddressMainAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:null-main-addresses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For accounts which address are null, get addresses from wallet.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        AccountRepo $AccountRepo,
        AccountServiceInterface $AccountService
    ) {
        parent::__construct();
        $this->AccountRepo = $AccountRepo;
        $this->AccountService = $AccountService;
        $this->coins = config('coin');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $eths = 0;
        $trxs = 0;
        $accounts = Account::whereNotNull('address')
            ->where('coin', 'USDT-ERC20')
            ->get();
        foreach ($accounts as $account) {
            $eth_account = $this->AccountRepo->findByUserCoinOrCreate($account->user, 'ETH');
            if (is_null($eth_account->address)) {
                $this->AccountService->getWalletAddress($account->user, 'ETH');
                $eths++;
            }
        }
        $this->line("{$eths} ETH address updated");

        $accounts = Account::whereNotNull('address')
            ->where('coin', 'USDT-TRC20')
            ->get();
        foreach ($accounts as $account) {
            $trx_account = $this->AccountRepo->findByUserCoinOrCreate($account->user, 'TRX');
            if (is_null($trx_account->address)) {
                $this->AccountService->getWalletAddress($account->user, 'TRX');
                $trxs++;
            }
        }
        $this->line("{$trxs} TRX address updated");
    }
}
