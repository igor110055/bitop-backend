<?php

namespace Database\Seeders;

use Dec\Dec;
use Illuminate\Database\Seeder;
use App\Models\{
    FeeSetting,
    Group,
};

class DefaultOrderFeeSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $type = FeeSetting::TYPE_ORDER;
        $coins = config('core')['coin']['all'];

        # inactivate existing settings
        FeeSetting::where('type', $type)
            ->where('is_active', true)
            ->update([
                'is_active' => false
            ]);

        foreach ($coins as $coin) {
            FeeSetting::create([
                'applicable_id' => null,
                'applicable_type' => null,
                'coin' => $coin,
                'type' => $type,
                'range_start' => '0',
                'range_end' => null,
                'value' => '0.3',
                'unit' => '%',
                'is_active' => true
            ]);
        }
    }
}
