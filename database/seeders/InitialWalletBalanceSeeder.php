<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WalletBalance;

class InitialWalletBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_keys(config('coin')) as $coin) {
            if (is_null(WalletBalance::where('coin', $coin)->first())) {
                $b = WalletBalance::create([
                    'coin' => $coin,
                    'balance' => 0,
                ]);
                dump($b->toArray());
            }
        }
    }
}
