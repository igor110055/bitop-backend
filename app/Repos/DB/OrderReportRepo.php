<?php

namespace App\Repos\DB;

use Dec\Dec;
use App\Models\{
    Order,
    OrderReport,
};

class OrderReportRepo extends BaseReportRepo implements \App\Repos\Interfaces\OrderReportRepo
{
    public function __construct(OrderReport $OrderReport) {
        parent::__construct();
        $this->Report = $OrderReport;
        $this->attributes = [
            'order_count',
            'order_amount',
            'order_price',
            'share_amount',
            'share_price',
            'profit',
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
            'order_count' => $this->countOrders($from, $to),
            'order_amount' => null,
            'order_price' => Dec::create(0), # sum
            'share_amount' => null,
            'share_price' => Dec::create(0), # sum
            'profit' => Dec::create(0), # sum
        ];
        foreach ($this->coins as $coin) {
            $report[$coin] = [
                'coin' => $coin,
                'exchange_rate' => $ExchangeRepo
                ->getLatest($coin, $to)
                ->price,
                'group_id' => null,
                'order_count' => 0,
                'order_amount' => Dec::create(0),
                'order_price' => Dec::create(0),
                'share_amount' => Dec::create(0),
                'share_price' => Dec::create(0),
                'profit' => Dec::create(0),
            ];
        }
        foreach ($this->groups as $group) {
            $report[$group] = [
                'coin' => null,
                'exchange_rate' => null,
                'group_id' => $group,
                'order_count' => 0,
                'order_amount' => null,
                'order_price' => Dec::create(0),
                'share_amount' => null,
                'share_price' => Dec::create(0),
                'profit' => Dec::create(0),
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
                    'order_count' => 0,
                    'order_amount' => Dec::create(0),
                    'order_price' => Dec::create(0),
                    'share_amount' => Dec::create(0),
                    'share_price' => Dec::create(0),
                    'profit' => Dec::create(0),
                ];
            }
        }
        return $report;
    }

    protected function countOrders($from, $to)
    {
        return app()->make(OrderRepo::class)
            ->queryOrder([
                ['status', '=', Order::STATUS_COMPLETED],
                ['completed_at', '>=', $from],
                ['completed_at', '<', $to]
            ])->count();
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
            if (in_array($key, ['order_price', 'share_price', 'profit'])) {
                $data = formatted_price($data);
            }
            if (in_array($key, ['order_amount', 'share_amount'])) {
                $data = formatted_coin_amount($data, data_get($row, 'coin'));
            }
            $result[] = [$index, $data];
        }
        return $result;
    }
}
