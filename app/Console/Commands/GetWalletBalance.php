<?php

namespace App\Console\Commands;

use Dec\Dec;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Services\WalletServiceInterface;

class GetWalletBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:balance {coin?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check wallet balance detail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(WalletServiceInterface $WalletService)
    {
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
            $this->checkCoin($coin);
        } else {
            foreach ($this->coins as $coin => $attributes) {
                $this->checkCoin($coin);
            }
        }
    }

    protected function checkCoin($coin)
    {
        $wallet_res = $this->WalletService
            ->getBalanceByCoin($coin);
        $this->info(print_r($wallet_res, true));
    }
}
