<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            Iso3166sTableSeeder::class,
            DefaultGroupSeeder::class,
            BanksTableSeeder::class,
            DefaultAgencySeeder::class,
            DefaultWithdrawalFeeDiscountSeeder::class,
            DefaultLimitationSeeder::class,
            DefaultConfigSeeder::class,
            RootSeeder::class,
            InitialWalletBalanceSeeder::class,
        ]);
    }
}
