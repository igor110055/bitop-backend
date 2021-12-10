<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Repos\Interfaces\ConfigRepo;
use App\Models\Config;

class ConfigController extends AdminController
{
    public function __construct(
        ConfigRepo $ConfigRepo
    ) {
        parent::__construct();
        $this->ConfigRepo = $ConfigRepo;
        $this->coins = array_keys(config('coin'));
        $this->middleware(['can:edit-configs']);
    }

    public function index()
    {
        $wallet_configs = $this->ConfigRepo->get(Config::ATTRIBUTE_WALLET);
        $withdrawal_fee_factor = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR);
        $withdrawal_limit = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_LIMIT);
        $app_versions = $this->ConfigRepo->get(Config::ATTRIBUTE_APP_VERSION);

        return view('admin.configs', [
            'wallet_configs' => $wallet_configs,
            'withdrawal_fee_factor' => $withdrawal_fee_factor,
            'withdrawal_limit' => $withdrawal_limit,
            'app_versions' => $app_versions,
            'coins' => $this->coins,
        ]);
    }

    public function storeWalletActivation(Request $request)
    {
        $input = $request->all();

        $wallet_value = [
            'deactivated' => (bool)data_get($input, 'wallet.deactivated'),
        ];
        $this->ConfigRepo
            ->create(
                Config::ATTRIBUTE_WALLET,
                $wallet_value
            );
        return redirect()->route('admin.configs.index')->with('flash_message', ['message' => '設定完成']);
    }

    public function storeWithdrawalFeeFactor(Request $request)
    {
        $data = [];
        $values = $request->validate([
            '*_base' => 'required|numeric',
            '*_pw_ratio' => 'required|numeric',
        ]);
        foreach ($this->coins as $coin) {
            $data[$coin]['base'] = $values["{$coin}_base"];
            $data[$coin]['pw_ratio'] = $values["{$coin}_pw_ratio"];
        }
        $this->ConfigRepo->create(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, $data);
        return redirect()->route('admin.configs.index')->with('flash_message', ['message' => '設定完成']);
    }

    public function storeAppVersionSetting(Request $request)
    {
        $data = [];
        $values = $request->validate([
            '*_latest' => 'required|regex:/^[1-9]+\.\d+\.\d+$/',
            '*_min' => 'required|regex:/^[1-9]+\.\d+\.\d+$/',
        ]);
        foreach (['web', 'ios', 'android'] as $platform) {
            $data[$platform]['latest'] = $values["{$platform}_latest"];
            $data[$platform]['min'] = $values["{$platform}_min"];
        }
        $this->ConfigRepo->create(Config::ATTRIBUTE_APP_VERSION, $data);
        return redirect()->route('admin.configs.index')->with('flash_message', ['message' => '設定完成']);
    }

    public function storeWithdrawalLimit(Request $request)
    {
        $data = [];
        $values = $request->validate([
            'daily_limit' => 'required|numeric',
        ]);
        $data['daily'] = $values['daily_limit'];
        $this->ConfigRepo->create(Config::ATTRIBUTE_WITHDRAWAL_LIMIT, $data);
        return redirect()->route('admin.configs.index')->with('flash_message', ['message' => '設定完成']);
    }
}
