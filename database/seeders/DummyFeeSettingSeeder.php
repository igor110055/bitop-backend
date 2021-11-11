<?php

namespace Database\Seeders;

use Dec\Dec;
use Illuminate\Database\Seeder;
use App\Models\{
    FeeSetting,
    Group,
};

class DummyFeeSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = FeeSetting::TYPES;
        $coins = config('core')['coin']['all'];

        # inactivate existing settings
        FeeSetting::where('is_active', true)->update(['is_active' => false]);

        foreach ($types as $type) {
            foreach ($coins as $coin) {
                $data = [
                    [
                        'applicable_id' => null,
                        'applicable_type' => null,
                        'coin' => $coin,
                        'type' => $type,
                        'range_start' => '0',
                        'range_end' => null,
                        'value' => '0.1',
                        'unit' => '%',
                        'is_active' => true
                    ],
                ];
                foreach ($data as $d) {
                    FeeSetting::create($d);
                }
            }
        }
    }
}
