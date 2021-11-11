<?php

namespace App\Repos\DB;

use Dec\Dec;
use App\Models\{
    Advertisement,
    AdReport,
};

class AdReportRepo extends BaseReportRepo implements \App\Repos\Interfaces\AdReportRepo
{
    public function __construct(AdReport $AdReport) {
        parent::__construct();
        $this->Report = $AdReport;
        $this->attributes = [
            'ad_count',
            'buy_ad_count',
            'buy_ad_amount',
            'buy_ad_price',
            'sell_ad_count',
            'sell_ad_amount',
            'sell_ad_price',
        ];
    }

    public function initReport($from, $to)
    {
        $ExchangeRepo = app()->make(CoinExchangeRateRepo::class);
        $report = [];
        $report['system'] = [
            'coin' => null,
            'exchange_rate' => null,
            'group_id' => null,
            'ad_count' => $this->countAds($from, $to),
            'buy_ad_count' => $this->countAds($from, $to, Advertisement::TYPE_BUY),
            'buy_ad_amount' => null,
            'buy_ad_price' => Dec::create(0), # sum
            'sell_ad_count' => $this->countAds($from, $to, Advertisement::TYPE_SELL),
            'sell_ad_amount' => null,
            'sell_ad_price' => Dec::create(0), # sum
        ];
        foreach ($this->coins as $coin) {
            $report[$coin] = [
                'coin' => $coin,
                'exchange_rate' => $ExchangeRepo
                ->getLatest($coin, $to)
                ->price,
                'group_id' => null,
                'ad_count' => 0,
                'buy_ad_count' => 0,
                'buy_ad_amount' => Dec::create(0),
                'buy_ad_price' => Dec::create(0), # sum
                'sell_ad_count' => 0,
                'sell_ad_amount' => Dec::create(0),
                'sell_ad_price' => Dec::create(0), # sum
            ];
        }
        foreach ($this->groups as $group) {
            $report[$group] = [
                'coin' => null,
                'exchange_rate' => null,
                'group_id' => $group,
                'ad_count' => 0,
                'buy_ad_count' => 0,
                'buy_ad_amount' => null,
                'buy_ad_price' => Dec::create(0), # sum
                'sell_ad_count' => 0,
                'sell_ad_amount' => null,
                'sell_ad_price' => Dec::create(0), # sum
            ];
        }
        foreach ($this->coins as $coin) {
            foreach ($this->groups as $group) {
                $report["{$coin}-{$group}"] = [
                    'coin' => $coin,
                    'exchange_rate' => $ExchangeRepo
                    ->getLatest($coin, $to)
                    ->price,
                    'group_id' => $group,
                    'ad_count' => 0,
                    'buy_ad_count' => 0,
                    'buy_ad_amount' => Dec::create(0),
                    'buy_ad_price' => Dec::create(0), # sum
                    'sell_ad_count' => 0,
                    'sell_ad_amount' => Dec::create(0),
                    'sell_ad_price' => Dec::create(0), # sum
                ];
            }
        }
        return $report;
    }

    protected function countAds($from, $to, $type = null)
    {
        $query = [
            ['status', '=', Advertisement::STATUS_AVAILABLE],
            ['created_at', '>=', $from],
            ['created_at', '<', $to],
        ];
        if (in_array($type, Advertisement::TYPES)) {
            $query[] = ['type', '=', $type];
        }
        return app()->make(AdvertisementRepo::class)
            ->queryAdvertisement($query)->count();
    }

    public function getChartData($from, $to, $group_id = null)
    {
        $dates = date_ticks($from, $to);
        $result = [];
        foreach ($this->coins as $coin) {
            $reports = $this->getSpecificByDates($dates, $coin, $group_id);
            foreach ($this->attributes as $attribute) {
                $result[$attribute][] = [
                    'label' => $coin,
                    'data' => $this->formatData($reports, $dates, $attribute)
                ];
            }
        }
        return $result;
    }

    protected function formatData($row, $dates, $key)
    {
        $result = [];
        foreach ($dates as $index => $date) {
            $data = data_get($row, "{$date}.{$key}", 0);
            if (in_array($key, ['buy_ad_price', 'sell_ad_price'])) {
                $data = formatted_price($data);
            }
            if (in_array($key, ['buy_ad_amount', 'sell_ad_amount'])) {
                $data = formatted_coin_amount($data, data_get($row, 'coin'));
            }
            $result[] = [$index, $data];
        }
        return $result;
    }
}
