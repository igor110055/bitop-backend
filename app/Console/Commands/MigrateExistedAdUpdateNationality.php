<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use App\Models\Advertisement;
use App\Repos\Interfaces\AdvertisementRepo;

class MigrateExistedAdUpdateNationality extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:ad-nationality';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existed ad\'s nationality';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        AdvertisementRepo $ar
    )
    {
        $this->AdvertisementRepo = $ar;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ads = Advertisement::all();

        foreach ($ads as $ad) {
            $bank_accounts = $ad->bank_accounts;
            $bank_accounts_nationalities = json_encode(array_unique($bank_accounts->pluck('bank.nationality')->toArray()));
            $this->AdvertisementRepo->setAttribute($ad, ['nationality' => $bank_accounts_nationalities]);
            $this->info("Ad {$ad->id} updated.");
        }
    }
}
