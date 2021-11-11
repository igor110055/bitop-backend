<?php

namespace App\Console\Commands;

use Dec\Dec;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Repos\Interfaces\{
    WalletBalanceRepo,
    AccountRepo,
};
use App\Services\WalletServiceInterface;
use App\Models\CheckWalletBalanceLog;

class CheckBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:balance {coin?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check system balance and wallet balance';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        AccountRepo $AccountRepo,
        WalletBalanceRepo $WalletBalanceRepo,
        WalletServiceInterface $WalletService
    ) {
        $this->AccountRepo = $AccountRepo;
        $this->WalletBalanceRepo = $WalletBalanceRepo;
        $this->WalletService = $WalletService;
        $this->coins = config('coin');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($coin = $this->argument('coin')) {
            $this->checkBalance($coin);
        } else {
            foreach ($this->coins as $coin => $attributes) {
                $this->checkBalance($coin);
            }
        }
    }

    protected function checkBalance(string $coin)
    {
        $balance = $this->WalletBalanceRepo->getBalance($coin);
        if (is_null($balance)) {
            dump("No system balance record");
            Log::alert("No system balance record");
            return;
        }
        $this->line("System {$coin} balance: {$balance}");

        $wallet_res = $this->WalletService->getBalanceByCoin($coin);

        $wallet_free_balance = data_get($wallet_res, 'free_balance', '0');
        $wallet_addresses_balance = data_get($wallet_res, 'addresses_balance', '0');
        $wallet_change_balance = data_get($wallet_res, 'change_balance', '0');
        $wallet_balance = Dec::add($wallet_free_balance, $wallet_addresses_balance)
            ->add($wallet_change_balance); # this is the "real" total balance in wallet
        $this->line("Wallet {$coin} balance: {$wallet_balance}");

        CheckWalletBalanceLog::create([
            'coin' => $coin,
            'system_balance' => $balance,
            'balance' => data_get($wallet_res, 'balance', '0'),
            'free_balance' => data_get($wallet_res, 'free_balance', '0'),
            'addresses_balance' => data_get($wallet_res, 'addresses_balance', '0'),
            'addresses_free_balance' => data_get($wallet_res, 'addresses_free_balance', '0'),
            'change_balance' => data_get($wallet_res, 'change_balance', '0'),
        ]);

        # Wallet Balance >= System Balance
        if (Dec::lt($wallet_balance, $balance)) {
            $this->error("{$coin} system balance {$balance} > wallet balance {$wallet_balance} (free_balance {$wallet_free_balance} + addresses_balance ($wallet_addresses_balance))");
            Log::alert("{$coin} system balance {$balance} > wallet balance {$wallet_balance} (free_balance {$wallet_free_balance} + addresses_balance ($wallet_addresses_balance))");
        }

        # System Balance >= Total Account Balance
        $account_table_balance = $this->AccountRepo->getBalancesSum($coin);
        $this->line("Accounts {$coin} balance: {$account_table_balance}");
        if (Dec::lt($balance, $account_table_balance)) {
            $this->error("System balance {$coin} {$balance} is less than total balance in accounts table {$account_table_balance}");
            Log::alert("System balance {$coin} {$balance} is less than total balance in accounts table {$account_table_balance}");
        }

        # Manual Deposit/Withdrawl and Profit >= Minimum Threshold
        if ($threshold = data_get($this->coins, "{$coin}.min_threshold")) {
            $extra_balance = Dec::sub($balance, $account_table_balance);
            if (Dec::lt($extra_balance, $threshold)) {
                $this->error("Extra balance {$coin} {$extra_balance} is less than threshold {$threshold}");
                Log::alert("Extra balance {$coin} {$extra_balance} is less than threshold {$threshold}");
            }
        }
    }
}
