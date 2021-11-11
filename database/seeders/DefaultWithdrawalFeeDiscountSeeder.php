<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeSetting;

class DefaultWithdrawalFeeDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $coins = array_keys(config('coin'));
        $setting = [
            'BTC' => ['value' => 100],
            'ETH' => ['value' => 100],
            'TRX' => ['value' => 100],
            'USDT-ERC20' => ['value' => 100],
            'USDT-TRC20' => ['value' => 100],
        ];
        foreach ($coins as $coin) {
            if (is_null(FeeSetting::where('type', FeeSetting::TYPE_WITHDRAWAL)->where('coin', $coin)->first())) {
                $fee_setting = FeeSetting::create([
                    'type' => FeeSetting::TYPE_WITHDRAWAL,
                    'coin' => $coin,
                    'value' => $setting["$coin"]['value'],
                    'unit' => '%',
                ]);
                dump($fee_setting->toArray());
            }
        }
    }
}
