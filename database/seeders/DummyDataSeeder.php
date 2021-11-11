<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DummyCurrencyExchangeRatesSeeder::class,
            DummyFeeSettingSeeder::class,
            DummyAssetsSeeder::class,
        ]);
    }
}
