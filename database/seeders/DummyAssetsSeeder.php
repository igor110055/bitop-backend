<?php

namespace Database\Seeders;

use Dec\Dec;
use Illuminate\Database\Seeder;
use App\Models\{
    Agency,
    CurrencyExchangeRate,
    Group,
};
use App\Repos\Interfaces\{
    AssetRepo,
    CurrencyExchangeRateRepo,
};

class DummyAssetsSeeder extends Seeder
{
    public function __construct(CurrencyExchangeRateRepo $CurrencyRepo, AssetRepo $AssetRepo)
    {
        $this->CurrencyExchangeRateRepo = $CurrencyRepo;
        $this->AssetRepo = $AssetRepo;
        $this->coins = config('core.coin.all');
        $this->currencies = config('core.currency.all');
        $this->base = config('core.currency.base');
        $this->price_types = CurrencyExchangeRate::PRICE_TYPES;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = config('core.currency.all');
        $bas = config('core.currency.base');
        $scale = config('core.currency.scale');
        $agency = Agency::findOrFail(Agency::DEFAULT_AGENCY_ID);
        $group = Group::findOrFail(Group::DEFAULT_GROUP_ID);

        $dummy = [
            'USD' => '100000',
            'TWD' => '1000000',
            'CNY' => '300000',
            'HKD' => '400000',
        ];

        foreach ($dummy as $currency => $amount) {
            $unit_price = $this->CurrencyExchangeRateRepo
                ->getLatest($currency, $group)
                ->mid;
            $asset = $this->AssetRepo->findByAgencyCurrencyOrCreate($agency, $currency);
            $this->AssetRepo->depositByAsset($asset, $amount, $unit_price);
        }
    }
}
