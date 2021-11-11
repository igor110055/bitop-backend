<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    User,
    Config,
};

class DefaultConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $root_id = env('ROOT_ID', '00000000000000');
        if ($admin = User::find($root_id)) {

            if (!$this->getConfig(Config::ATTRIBUTE_WALLET)) {
                dump(Config::create([
                    'admin_id' => $admin->id,
                    'attribute' => Config::ATTRIBUTE_WALLET,
                    'value' => ['deactivated' => false],
                    'is_active' => true,
                ])->toArray());
            }

            if (!$this->getConfig(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR)) {
                dump(Config::create([
                    'admin_id' => $admin->id,
                    'attribute' => Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR,
                    'value' => [
                        'BTC' => [
                            'base' => '0',
                            'pw_ratio' => '1.25',
                        ],
                        'ETH' => [
                            'base' => '0.04',
                            'pw_ratio' => '1.25',
                        ],
                        'USDT-ERC20' => [
                            'base' => '2',
                            'pw_ratio' => '1.25',
                        ],
                        'USDT-TRC20' => [
                            'base' => '2',
                            'pw_ratio' => '0',
                        ],
                        'TRX' => [
                            'base' => '1',
                            'pw_ratio' => '0',
                        ],
                    ],
                    'is_active' => true,
                ])->toArray());
            }

            if (!$this->getConfig(Config::ATTRIBUTE_WITHDRAWAL_LIMIT)) {
                dump(Config::create([
                    'admin_id' => $admin->id,
                    'attribute' => Config::ATTRIBUTE_WITHDRAWAL_LIMIT,
                    'value' => ['daily' => config('core.withdrawal.limit.daily')],
                    'is_active' => true,
                ])->toArray());
            }

        } else {
            dump("DefaultConfigSeeder failed: Root ID user doesn't exist.");
        }
    }

    protected function getConfig(string $attribute)
    {
        return Config::where('attribute', $attribute)
            ->where('is_active', true)
            ->first();
    }
}
