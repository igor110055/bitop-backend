<?php

namespace App\Services;

use DB;
use Dec\Dec;
use Illuminate\Support\Facades\Log;
use App\Exceptions\{
    Core\BadRequestError,
    VendorException,
};
use App\Repos\Interfaces\{
    CurrencyExchangeRateRepo,
    SystemActionRepo,
};
use App\Models\SystemAction;

class CurrencyExchangeService implements CurrencyExchangeServiceInterface
{
    public function __construct(CurrencyExchangeRateRepo $cer)
    {
        $this->CurrencyExchangeRateRepo = $cer;
        $this->currencies = config('core.currency.all');
        $this->cl_key = config('services.currencylayer.key');
        $this->cl_link = config('services.currencylayer.link');
        $this->huobi_link = config('services.huobi.usdt-cny');
        $this->tw_bank_csv_file = config('services.tw-bank.csv-file');
    }

    protected function formula($cny, $usdt_cny, $hkd_cny)
    {
        $currencies = [];
        $currencies['TWD']['ask'] = (string) Dec::add($cny, 0.07)->mul($usdt_cny)->add(0.3, 6);
        $currencies['TWD']['bid'] = (string) Dec::add($cny, 0.07)->mul($usdt_cny)->sub(0.1, 6);
        $currencies['CNY']['ask'] = (string) Dec::sub($usdt_cny, 0.05, 6);
        $currencies['CNY']['bid'] = (string) Dec::sub($usdt_cny, 0.06, 6);
        $currencies['USD']['ask'] = '1.000000';
        $currencies['USD']['bid'] = '0.998000';
        $currencies['HKD']['ask'] = (string) Dec::div($currencies['CNY']['ask'], $hkd_cny, 6);
        $currencies['HKD']['bid'] = (string) Dec::div($currencies['CNY']['bid'], $hkd_cny, 6);
        return $currencies;
    }

    public function currencyExchangeRates()
    {
        return $this->formula(
            $this->getTWBankCurrency('CNY', 'bid'),
            $this->getHuobiUSDTtoCNYrate(),
            $this->getHKDtoCNYrate()
        );
    }

    public function update()
    {
        $SystemActionRepo = app()->make(SystemActionRepo::class);
        DB::transaction(function () use ($SystemActionRepo) {
            foreach ($this->currencyExchangeRates() as $currency => $value) {
                $this->CurrencyExchangeRateRepo->create(
                    $currency,
                    $value['bid'],
                    $value['ask']
                );
            }
            $SystemActionRepo->create([
                'type' => SystemAction::TYPE_UPDATE_CURRENCY_EXCHANGE_RATE,
                'description' => 'System update currency exchange rates',
            ]);
        });
    }

    public function getTWBankCurrency($currency, $side)
    {
        if ($side !== 'bid' and $side !== 'ask') {
            throw new BadRequestError;
        }
        $res = $this->getTWBankCurrencies([$currency]);
        return bcdiv($res[$currency][$side], 1, 2);
    }

    public function getTWBankCurrencies(array $currencies = null)
    {
        $res = $this->fetchTWBankExchangeRate();
        if (is_null($currencies)) {
            return $res;
        }
        return array_intersect_key($res, array_flip($currencies));
    }

    protected function fetchTWBankExchangeRate()
    {
        ini_set("auto_detect_line_endings", true);
        $coll = collator_create('zh_Hant_TW');
        try {
            $file = fopen($this->tw_bank_csv_file, "r");
        } catch (\Throwable $e) {
            Log::critical("TW Bank currency exchange rate csv file not found, error message: ".$e->getMessage());
            throw new VendorException("TW Bank currency exchange rate csv file not found");
        }
        $res = [];
        $row = 0;
        try {
            while(($data = fgetcsv($file, 500)) !== false) {
                if ($row === 0) {
                    if (
                        (collator_compare($coll, $data[0], '幣別') and $data[0] !== 'Currency') or
                        (collator_compare($coll, $data[1], '匯率') and $data[1] !== 'Rate') or
                        (collator_compare($coll, $data[11], '匯率') and $data[11] !== 'Rate') or
                        (collator_compare($coll, $data[2], '現金') and $data[2] !== 'Cash') or
                        (collator_compare($coll, $data[12], '現金') and $data[12] !== 'Cash')
                    ) {
                        throw new VendorException('TW Bank CSV file column incorrect');
                    }
                    $row++;
                    continue;
                }
                if (
                    (collator_compare($coll, $data[1], '本行買入') and $data[1] !== 'Buying') or
                    (collator_compare($coll, $data[11], '本行賣出') and $data[11] !== 'Selling')
                ) {
                    throw new VendorException('TW Bank CSV file column incorrect');
                }
                $res[$data[0]]['bid'] = $data[2];
                $res[$data[0]]['ask'] = $data[12];
                $row++;
            }
        } catch (VendorException $e) {
            Log::critical("TW CSV file column incorrect");
            throw $e;
        }
        fclose($file);
        return $res;
    }

    public function getHKDtoCNYrate()
    {
        foreach($this->fetchCurrencyLayerExchangeRate() as $res) {
            if ($res['currency'] === 'HKD') {
                return (string) $res['rate'];
            }
        }
        Log::critical("CurrencyLayer API reponse error, cannot fetch hkd to cny rate");
        throw new VendorException("CurrencyLayer API error");
    }

    public function fetchCurrencyLayerExchangeRate()
    {
        $source = "CNY";
        try {
            $query = http_build_query([
                'access_key' => $this->cl_key,
                'currencies' => implode(',', $this->currencies),
                'source' => $source,
                'format' => 1,
            ]);
            if (($body = @file_get_contents("$this->cl_link?$query")) !== false) {
                $response = json_decode($body, true);
                if (is_array($response) and
                    $response['success'] === true and
                    isset($response['quotes'])
                ) {
                    $result = [];
                    foreach ($this->currencies as $currency) {
                        $key = "$source$currency";
                        if (!empty($response['quotes'][$key])) {
                            $result[] = [
                                'currency' => $currency,
                                'rate' => Dec::create(1)->div($response['quotes'][$key], 4),
                            ];
                        }
                    }
                    return $result;
                }
                Log::critical("CurrencyLayer API error, error code: {$response['error']['code']}, error info: {$response['error']['info']}");
                throw new VendorException("CurrencyLayer API error");
            }
            throw new VendorException('Fail to file_get_contents CurrencyLayer API');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function getHuobiUSDTtoCNYrate()
    {
        try {
            if (($body = @file_get_contents($this->huobi_link)) !== false) {
                $response = json_decode($body, true);
                if (is_array($response) and $response['code'] === 200) {
                    $prices = array_map(function ($item) {
                        return $item['price'];
                    }, $response['data']);
                    return $this->getMode($this->getEachPriceCount($prices));
                }
                Log::critical("Huobi exchange rate error, error code: {$response['code']}, error info: {$response['message']}");
                throw new VendorException("Huobi exchange rate error");
            }
            throw new VendorException('Fail to get huobi exchange rate');
        } catch (\Throwable $e) {
            throw $e;
        }

    }

    protected function getEachPriceCount($prices)
    {
        $count = [];
        foreach ($prices as $price) {
            $price = strval($price);
            if (!isset($count[$price])) {
                $count[$price] = 0;
            }
            $count[$price]++;
        }
        return $count;
    }

    protected function getMode($count)
    {
        $max = 0;
        $mode = [];
        # if highest prices's count >= 3, use the highest price.
        $highest = reset($count);  # get the first elemet of array $count;
        if ($highest >= 3) {
            return key($count);
        }

        foreach ($count as $key => $value) {
            if ($max < $value) {
                $max = $value;
                $mode = [$key];
            } elseif ($max === $value) {
                $mode[] = $key;
            }
            sort($mode, SORT_STRING);
            if (count($mode) === 1) {
                return $mode[0]; # mode
            } else {
                $total = count($mode);
                if (is_int($total/2)) return $mode[$total/2]; # even
                else return $mode[floor($total/2)]; # odd
            }
        }
    }
}
