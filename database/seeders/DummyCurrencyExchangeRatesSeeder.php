<?php

namespace Database\Seeders;

use Dec\Dec;
use Illuminate\Database\Seeder;
use App\Models\{
    CurrencyExchangeRate,
    Group,
};

class DummyCurrencyExchangeRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $iterations = 3;
        $groups = Group::all();
        $dummy = [
            'USD' => [
                'bid' => '30.675',
                'ask' => '31.365',
            ],
            'HKD' => [
                'bid' => '3.795',
                'ask' => '4.011',
            ],
            'CNY' => [
                'bid' => '4.411',
                'ask' => '4.573',
            ],
        ];

        $this->create(null, 'TWD');
        for ($i = 0; $i < $iterations; $i++) {
            foreach ($dummy as $currency => $values) {
                $this->create(null, $currency, $values);
            }
        }
        foreach ($groups as $group) {
            for ($i = 0; $i < $iterations; $i++) {
                foreach ($dummy as $currency => $values) {
                    $this->create($group, $currency, $values);
                }
            }
        }
    }

    protected function create($group = null, $currency, $values = [])
    {
        if ($currency === 'TWD') {
            $data = ['bid' => '1', 'ask' => '1', 'mid' => '1'];
        } else {
            $data = $this->generateData($values);
        }
        CurrencyExchangeRate::create([
            'group_id' => data_get($group, 'id'),
            'currency' => $currency,
            'bid' => $data['bid'],
            'ask' => $data['ask'],
            'mid' => $data['mid'],
        ]);
    }

    protected function generateData($values = [])
    {
        $bid = $this->generateNum($values['bid']);
        $ask = $this->generateNum($values['ask']);
        $mid = Dec::add($ask, $bid)->div(2);
        return [
            'bid' => (string)$bid,
            'ask' => (string)$ask,
            'mid' => (string)$mid,
        ];
    }

    protected function generateNum($num)
    {
        $rand = random_int(-100 ,100);
        $rand = Dec::div($rand, 10000)->add(1);
        return Dec::mul($num, $rand);
    }
}
