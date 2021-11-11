<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Limitation;

class DefaultLimitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $coins = config('core')['coin']['all'];
        $setting = [
            'BTC' => ['min' => 0.01, 'max' => 10],
            'USDT-ERC20' => ['min' => 1, 'max' => 100000],
            'ETH' => ['min' => 0.1, 'max' => 5000],
            'TRX' => ['min' => 0.1, 'max' => 50000],
            'USDT-TRC20' => ['min' => 1, 'max' => 100000],
//            'LLT' => ['min' => 0.01, 'max' => 1000],
//            'EOS' => ['min' => 0.01, 'max' => 1000],
        ];

        foreach ($coins as $coin) {
            if (is_null(Limitation::where('type', Limitation::TYPE_WITHDRAWAL)->where('coin', $coin)->first())) {
                Limitation::create([
                    'type' => Limitation::TYPE_WITHDRAWAL,
                    'coin' => $coin,
                    'min' => $setting["$coin"]['min'],
                    'max' => $setting["$coin"]['max'],
                ]);
            }
        }
    }
}
