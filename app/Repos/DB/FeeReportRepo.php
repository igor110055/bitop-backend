<?php

namespace App\Repos\DB;

use Dec\Dec;
use App\Models\{
    FeeReport,
};

class FeeReportRepo extends BaseReportRepo implements \App\Repos\Interfaces\FeeReportRepo
{
    public function __construct(FeeReport $FeeReport) {
        parent::__construct();
        $this->Report = $FeeReport;
        $this->attributes = [
            'order_fee',
            'order_fee_price',
            'withdrawal_fee',
            'withdrawal_fee_price',
            'wallet_fee',
            'wallet_fee_price',
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
            'order_fee' => null,
            'order_fee_price' => Dec::create(0), # sum
            'withdrawal_fee' => null,
            'withdrawal_fee_price' => Dec::create(0), # sum
            'wallet_fee' => null,
            'wallet_fee_price' => Dec::create(0), # sum
        ];
        foreach ($this->coins as $coin) {
            $report[$coin] = [
                'coin' => $coin,
                'exchange_rate' => $ExchangeRepo
                ->getLatest($coin, $to)
                ->price,
                'group_id' => null,
                'order_fee' => Dec::create(0),
                'order_fee_price' => Dec::create(0),
                'withdrawal_fee' => Dec::create(0),
                'withdrawal_fee_price' => Dec::create(0),
                'wallet_fee' => Dec::create(0),
                'wallet_fee_price' => Dec::create(0),
            ];
        }
        foreach ($this->groups as $group) {
            $report[$group] = [
                'coin' => null,
                'exchange_rate' => null,
                'group_id' => $group,
                'order_fee' => null,
                'order_fee_price' => Dec::create(0),
                'withdrawal_fee' => null,
                'withdrawal_fee_price' => Dec::create(0),
                'wallet_fee' => null,
                'wallet_fee_price' => Dec::create(0),
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
                    'order_fee' => Dec::create(0),
                    'order_fee_price' => Dec::create(0),
                    'withdrawal_fee' => Dec::create(0),
                    'withdrawal_fee_price' => Dec::create(0),
                    'wallet_fee' => Dec::create(0),
                    'wallet_fee_price' => Dec::create(0),
                ];
            }
        }
        return $report;

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
            if (in_array($key, ['order_fee_price', 'withdrawal_fee_price', 'wallet_fee_price'])) {
                $data = formatted_price($data);
            }
            if (in_array($key, ['order_fee', 'withdrawal_fee', 'wallet_fee'])) {
                $data = formatted_coin_amount($data, data_get($row, 'coin'));
            }
            $result[] = [$index, $data];
        }
        return $result;
    }
}
