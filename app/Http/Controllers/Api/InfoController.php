<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Exceptions\Core\{
    BadRequestError,
    WrongRequestHeaderError,
};
use App\Http\Requests\{
    CoinRequest,
    CurrencyRequest,
};
use App\Models\{
    Config,
    Iso3166,
    DeviceToken,
};
use App\Services\WalletServiceInterface;
use App\Repos\Interfaces\{
    ConfigRepo,
    DeviceTokenRepo,
};

class InfoController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->coins = config('coin');
        $this->currencies = config('currency');
        $this->coin_map = config('services.wallet.coin_map');
    }

    public function getConfig(Request $request, ConfigRepo $ConfigRepo)
    {
        return [
            'version' => $ConfigRepo->get(Config::ATTRIBUTE_APP_VERSION),
            'payment_window' => $ConfigRepo->get(Config::ATTRIBUTE_PAYMENT_WINDOW),
            'invitation_required' => $ConfigRepo->get(Config::ATTRIBUTE_INVITATION_REQUIRED),
            'coins' => $this->getCoinInfo($request),
            'currencies' => $this->getCurrencyInfo($request),
        ];
    }

    public function getVersion(
        Request $request,
        DeviceTokenRepo $DeviceTokenRepo,
        ConfigRepo $ConfigRepo
    ) {
        $this->registerDeviceToken($request, $DeviceTokenRepo);
        $config = $ConfigRepo->get(Config::ATTRIBUTE_APP_VERSION);
        $platform = $request->input('platform');
        if (!is_null($platform) and isset($config[$platform])) {
            return $config[$platform];
        } else {
            return $config;
        }
    }

    public function getISO3166()
    {
        return Iso3166::all()->keyBy('alpha_2');
    }

    protected function getWalletCoinInfo()
    {
        $result = [];
        try {
            $res = app()->make(WalletServiceInterface::class)->getSupportedCoinList();
            if (is_null($res)) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }
        foreach ($res as $value) {
            $result[$value['id']] = $value['status'];
        }
        return $result;
    }

    public function getWalletStatus(
        Request $request,
        ConfigRepo $ConfigRepo
    ) {
        $this->coins = hide_beta_coins(auth()->user(), $this->coins);

        $response = [];
        if ($ConfigRepo->get(Config::ATTRIBUTE_WALLET, 'deactivated') === true) {
            foreach ($this->coins as $coin => $values) {
                $response[$coin] = [
                    'coin' => $coin,
                    'status' => 'inactive',
                ];
            }
        } else {
            $wallet_res = $this->getWalletCoinInfo();
            foreach ($this->coins as $coin => $values) {
                $response[$coin] =  [
                    'coin' => $coin,
                    'status' => data_get($wallet_res, $this->coin_map[$coin], 'inactive')
                ];
            }
        }
        if ($coin = $request->input('coin')) {
            if (array_key_exists($coin, $this->coins)) {
                return $response[$coin];
            } else {
                throw new BadRequestError;
            }
        }
        return $response;
    }

    public function getCoinInfo(Request $request)
    {
        $this->coins = hide_beta_coins(auth()->user(), $this->coins);

        $response = [];
        $visible = [
            'coin',
            'icon',
            'network',
            'has_tag',
            'tag_name',
            'confirmation',
            'decimal',
            'checksummable',
            'rank',
        ];
        $rank = 0;
        foreach ($this->coins as $coin => $values) {
            $values = array_merge($values, [
                'coin' => $coin,
                'rank' => $rank,
            ]);
            $response[$coin] = Arr::only($values, $visible);
            $rank++;
        }

        if ($coin = $request->input('coin')) {
            if (array_key_exists($coin, $this->coins)) {
                return $response[$coin];
            } else {
                throw new BadRequestError;
            }
        }
        return $response;
    }

    public function getCurrencyInfo(Request $request)
    {
        $response = [];
        foreach ($this->currencies as $currency => $values) {
            $values = array_merge($values, ['currency' => $currency]);
            $response[$currency] = $values;
        }

        $currency = $request->input('currency');
        if ($currency and in_array($currency, array_keys($this->currencies))) {
            return $response[$currency];
        }
        return $response;
    }

    # It's nowhere, just respond 200 or 201 for test & safe wallet callback
    public function nowhere(Request $request)
    {
        if ($request->isMethod('post')) {
            return response(null, 201);
        }
        return response(null, 200);
    }

    protected function registerDeviceToken($request, $DeviceTokenRepo)
    {
        if ($request->headers->has('X-PLATFORM') and
            $request->headers->has('X-SERVICE') and
            $request->headers->has('X-DEVICE-TOKEN')
        ) {
            if (empty($request->header('X-DEVICE-TOKEN'))) {
                return;
            }
            if (!in_array($request->header('X-PLATFORM'), DeviceToken::PLATFORMS) or
                !in_array($request->header('X-SERVICE'), DeviceToken::SERVICES)
            ) {
                throw new WrongRequestHeaderError;
            }
            $data = [
                'platform' => $request->header('X-PLATFORM'),
                'service' => $request->header('X-SERVICE'),
                'token' => $request->header('X-DEVICE-TOKEN'),
            ];
            $update = ['last_active_at' => Carbon::now()];
            if ($token = $DeviceTokenRepo->getUnique($data)) {
                return $DeviceTokenRepo->update($token, $update);
            } else {
                if ($user = auth()->user()) {
                    $update = array_merge(['user_id' => $user->id], $update);
                }
                return $DeviceTokenRepo
                    ->create(array_merge($update, $data));
            }
        }
    }
}
