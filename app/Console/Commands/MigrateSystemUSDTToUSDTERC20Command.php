<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Models\{
    Account,
    Address,
    Advertisement,
    CheckWalletBalanceLog,
    Deposit,
    FeeCost,
    FeeSetting,
    Limitation,
    Order,
    Transaction,
    Transfer,
    WalletBalanceLog,
    WalletBalance,
    WalletManipulation,
    Withdrawal,
    AccountReport,
    AdReport,
    FeeReport,
    OrderReport,
    TransferReport,
    WalletBalanceReport,
    WithdrawalDepositReport,
};

class MigrateSystemUSDTToUSDTERC20Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:usdt-erc20';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Migrate system tables 'coin' column from usdt to usdt-erc20";

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
        $tables = [
            Account::class => Account::query(),
            Address::class => Address::query(),
            Advertisement::class => Advertisement::query(),
            CheckWalletBalanceLog::class => CheckWalletBalanceLog::query(),
            Deposit::class => Deposit::query(),
            FeeCost::class => FeeCost::query(),
            FeeSetting::class => FeeSetting::query(),
            Limitation::class => Limitation::query(),
            Order::class => Order::query(),
            Transaction::class => Transaction::query(),
            Transfer::class => Transfer::query(),
            WalletBalanceLog::class => WalletBalanceLog::query(),
            WalletBalance::class => WalletBalance::query(),
            WalletManipulation::class => WalletManipulation::query(),
            Withdrawal::class => Withdrawal::query(),
            AccountReport::class => AccountReport::query(),
            AdReport::class => AdReport::query(),
            FeeReport::class => FeeReport::query(),
            OrderReport::class => OrderReport::query(),
            TransferReport::class => TransferReport::query(),
            WalletBalanceReport::class => WalletBalanceReport::query(),
            WithdrawalDepositReport::class => WithdrawalDepositReport::query(),
        ];
        $count = 0;
        foreach ($tables as $table => $query) {
            try {
                DB::transaction(function () use ($table, $query) {
                    $query->where('coin', 'USDT')->update(['coin' => 'USDT-ERC20']);
                    dump("$table table updated coin to USDT-ERC20");
                });
                $count++;
            } catch (\Throwable $e) {
                $this->error("$table table USDT coin update failed");
            }
        }
        $total = count($tables);
        dump("Total {$total} tables requires update; {$count} tables success");
    }
}
