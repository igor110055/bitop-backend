<?php

namespace App\Console\Commands;

use DB;
use Dec\Dec;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\{
    WalletBalanceLog,
    WalletManipulation,
};
use App\Repos\Interfaces\{
    WalletBalanceRepo,
    WalletBalanceLogRepo,
    WalletManipulationRepo,
};
use App\Services\WalletServiceInterface;

class SubmitWalletWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:withdrawal {coin} {amount} {address} {--tag=} {--callback=} {--is_full_payment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Submit a wallet withdrawal mannually.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        WalletBalanceRepo $WalletBalanceRepo,
        WalletBalanceLogRepo $WalletBalanceLogRepo,
        WalletManipulationRepo $WalletManipulationRepo,
        WalletServiceInterface $WalletService
    ) {
        $this->WalletBalanceRepo = $WalletBalanceRepo;
        $this->WalletBalanceLogRepo = $WalletBalanceLogRepo;
        $this->WalletManipulationRepo = $WalletManipulationRepo;
        $this->WalletService = $WalletService;
        $this->coins = config('coin');
        $this->wallet_reverse_coin_map = config('services.wallet.reverse_coin_map');
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
        $address = $this->argument('address');
        $amount = $this->argument('amount');
        $callback = $this->option('callback') ?? url('api/wallet/manual-withdrawal-callback');
        $tag = $this->option('tag');
        $is_full_payment = $this->option('is_full_payment');
        $client_withdrawal_id = 'ManualWithdrawal_'.millitime();

        assert(in_array($coin, array_keys($this->coins)));

        $res = $this->WalletService->withdrawal($coin, $address, $tag, $amount, $callback, $client_withdrawal_id, $is_full_payment);
        $this->WalletService->checkWithdrawalResponseParameter($res);

        DB::transaction(function () use ($coin, $res) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($coin);
            if ($fee_coin = data_get($this->coins, "{$coin}.fee_coin")) {
                $wallet_fee_balance = $this->WalletBalanceRepo->findForUpdateByCoin($fee_coin);
            }

            $is_full_payment = data_get($res, 'is_full_payment');
            if ($is_full_payment === true) {
                $amount = data_get($res, 'amount');
            } elseif ($is_full_payment === false) {
                $amount = data_get($res, 'dst_amount');
            } else {
                throw new VendorException("Wallet withdrawal request no is_full_payment parameter");
            }

            $manipulate = $this->WalletManipulationRepo->create([
                'coin' => $coin,
                'type' => WalletManipulation::TYPE_WITHDRAWAL,
                'wallet_id' => data_get($res, 'id'),
                'transaction' => data_get($res, 'transaction'),
                'amount' => $amount,
                'response' => $res,
            ]);

            # wallet balance
            $this->WalletBalanceRepo->withdraw($wallet_balance, $amount);
            $wallet_balance->refresh();

            # wallet balance log
            $this->WalletBalanceLogRepo->create(
                $manipulate,
                $wallet_balance,
                WalletBalanceLog::TYPE_MANUAL_WITHDRAWAL,
                (string) Dec::create($amount)->additiveInverse()
            );

            # wallet balance: wallet fee
            if (isset($wallet_fee_balance)) {
                $this->WalletBalanceRepo->withdraw(
                    $wallet_fee_balance,
                    data_get($res, 'fee', '0')
                );
                $wallet_fee_balance->refresh();

                # wallet balance log
                $this->WalletBalanceLogRepo->create(
                    $manipulate,
                    $wallet_fee_balance,
                    WalletBalanceLog::TYPE_WALLET_FEE,
                    (string) Dec::create(data_get($res, 'fee', '0'))->additiveInverse()
                );
            } else {
                $this->WalletBalanceRepo->withdraw(
                    $wallet_balance,
                    data_get($res, 'fee', '0')
                );
                $wallet_balance->refresh();

                # wallet balance log
                $this->WalletBalanceLogRepo->create(
                    $manipulate,
                    $wallet_balance,
                    WalletBalanceLog::TYPE_WALLET_FEE,
                    (string) Dec::create(data_get($res, 'fee', '0'))->additiveInverse()
                );
            }
        });

        $tag = $tag ?? 'null';
        $is_full_payment = $is_full_payment ? 'true' : 'false';
        $this->line("send:withdrawal, coin={$coin}, address={$address}, tag={$tag}, amount={$amount}, calback={$callback}, client_withdrawal_id={$client_withdrawal_id}, is_full_payment={$is_full_payment}");
        Log::alert("send:withdrawal, coin={$coin}, address={$address}, tag={$tag}, amount={$amount}, calback={$callback}, client_withdrawal_id={$client_withdrawal_id}, is_full_payment={$is_full_payment}");

        return;
    }
}
