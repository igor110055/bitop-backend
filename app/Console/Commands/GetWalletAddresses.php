<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\{
    WalletServiceInterface,
};

class GetWalletAddresses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:addresses {coin} {--address=} {--tag=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get wallet address by coin';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        WalletServiceInterface $ws
    ) {
        $this->WalletService = $ws;
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
        if ($address = $this->option('address')) {
            $addresses = $this->WalletService
                ->getAddress($coin, $address, $this->option('tag'));
        } else {
            $addresses = $this->WalletService
                ->getAllAddress($coin);
        }
        $this->info(print_r($addresses, true));
    }
}