<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CoinExchangeServiceInterface;

class UpdateCoinExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:coin-exchange-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update the coin exchange rate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CoinExchangeServiceInterface $exchange_service)
    {
        parent::__construct();
        $this->exchange_service = $exchange_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->exchange_service->update();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}
