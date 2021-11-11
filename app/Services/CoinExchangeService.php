<?php

namespace App\Services;

use DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\VendorException;
use App\Repos\Interfaces\{
    CoinExchangeRateRepo,
    SystemActionRepo,
};
use App\Models\SystemAction;

class CoinExchangeService implements CoinExchangeServiceInterface
{
    public function __construct(CoinExchangeRateRepo $exchange_rate)
    {
        $this->key = config('services')['coinmarketcap']['key'];
        $this->link = config('services')['coinmarketcap']['link'];
        $this->currencies = config('core')['currency']['all'];
        $this->base = config('core')['currency']['base'];
        $this->exchange_rate_repo = $exchange_rate;
        $this->coins = config('coin');
    }

    public function fetch()
    {
        $fetch_coins = [];
        foreach ($this->coins as $coin => $config) {
            $fetch_coins[] = data_get($config, 'base', $coin);
        }
        $fetch_coins = array_unique($fetch_coins);
        try {
            $parameters = [
                'symbol' => implode(',', $fetch_coins),
                'convert' => $this->base,
            ];
            $headers = [
                "Accepts: application/json",
                "X-CMC_PRO_API_KEY: $this->key",
            ];
            $query = http_build_query($parameters);
            $request = "$this->link?{$query}";

            $curl = curl_init(); // Get cURL resource
            curl_setopt_array($curl, array(
                CURLOPT_URL => $request,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => 1 // ask for raw response instead of bool
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);
            curl_close($curl);

            if (is_array($response) and
                $response['status']['error_code'] === 0 and
                isset($response['data'])
            ) {
                $result = [];
                foreach ($fetch_coins as $coin) {
                    if (!empty($response['data'][$coin]['quote'][$this->base]['price'])) {
                        $result[] = [
                            'coin' => $coin,
                            'price' => $response['data'][$coin]['quote'][$this->base]['price'],
                        ];
                    }
                }
                return $result;
            }
            Log::error("CoinMarketCap API error, error code: {$response['status']['error_code']}, error info: {$response['status']['error_message']}");
            throw new VendorException("CoinMarketCap API error");
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function update()
    {
        $SystemActionRepo = app()->make(SystemActionRepo::class);
        $res = $this->fetch();
        DB::transaction(function () use ($res, $SystemActionRepo) {
            collect($res)
                ->each(function ($data) {
                    $this->exchange_rate_repo->create([
                        'coin' => $data['coin'],
                        'price' => $data['price'],
                    ]);
                });
            $SystemActionRepo->create([
                'type' => SystemAction::TYPE_UPDATE_COIN_EXCHANGE_RATE,
                'description' => 'System update coin exchange rates',
            ]);
        });
    }
}
