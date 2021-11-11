<?php

namespace App\Console\Commands;

use DB;
use Dec\Dec;
use Illuminate\Console\Command;
use App\Repos\Interfaces\{
    ManipulationRepo,
    WalletBalanceRepo,
    WalletBalanceLogRepo,
};
use App\Models\WalletBalanceLog;

class WalletBalanceCorrectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correct:wallet-balance {--D|deposit} {--W|withdraw} {coin} {amount} {--note=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wallet balance correction';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ManipulationRepo $mr,
        WalletBalanceRepo $wbr,
        WalletBalanceLogRepo $wblr
    ) {
        parent::__construct();
        $this->coins = array_keys(config('coin'));
        $this->ManipulationRepo = $mr;
        $this->WalletBalanceRepo = $wbr;
        $this->WalletBalanceLogRepo = $wblr;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!(($d = $this->option('deposit')) xor ($w = $this->option('withdraw')))) {
            $this->error('should operate deposit or withdraw either one');
            return;
        }
        $coin = $this->argument('coin');
        $amount = $this->argument('amount');
        $note = $this->option('note');

        assert(in_array($coin, $this->coins));

        $log = DB::transaction(function () use ($coin, $amount, $note, $d) {
            $wallet_balance = $this->WalletBalanceRepo->findForUpdateByCoin($coin);

            $pre_note = "Manual correction by Admin";
            $manipulate = $this->ManipulationRepo->create(
                '00000000000000',
                $note ? $pre_note.": $note" : $pre_note
            );
            if ($d) {
                $this->WalletBalanceRepo->deposit($wallet_balance, $amount);
            } else {
                $this->WalletBalanceRepo->withdraw($wallet_balance, $amount);
                $amount = (string) Dec::create($amount)->additiveInverse();
            }
            $wallet_balance->refresh();
            return $this->WalletBalanceLogRepo->create(
                $manipulate,
                $wallet_balance,
                WalletBalanceLog::TYPE_MANUAL_CORRECTION,
                $amount
            );
        });
        dump("Wallet balance manual correction", $log->toArray());
        \Log::alert("Wallet balance manual correction", $log->toArray());
    }
}
