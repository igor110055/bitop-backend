<?php

namespace Database\Seeders;

use Dec\Dec;
use Illuminate\Database\Seeder;
use App\Models\{
    CoinExchangeRate,
};

class ControlCoinExchangeRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $control_coin = config('core.coin.control');
        $price = 100;

        CoinExchangeRate::create([
            'coin' => $control_coin,
            'price' => $price,
        ]);
    }
}
