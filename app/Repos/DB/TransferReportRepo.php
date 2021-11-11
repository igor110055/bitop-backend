<?php

namespace App\Repos\DB;

use Dec\Dec;
use App\Models\{
    Transfer,
    TransferReport,
};

class TransferReportRepo extends BaseReportRepo implements \App\Repos\Interfaces\TransferReportRepo
{
    public function __construct(TransferReport $TransferReport) {
        parent::__construct();
        $this->Report = $TransferReport;
        $this->attributes = [
            'transfer_count',
            'transfer_amount',
            'transfer_price',
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
            'transfer_count' => $this->countTransfers($from, $to),
            'transfer_amount' => null,
            'transfer_price' => Dec::create(0), # sum
        ];
        foreach ($this->coins as $coin) {
            $report[$coin] = [
                'coin' => $coin,
                'exchange_rate' => $ExchangeRepo
                ->getLatest($coin, $to)
                ->price,
                'group_id' => null,
                'transfer_count' => 0,
                'transfer_amount' => Dec::create(0),
                'transfer_price' => Dec::create(0),
            ];
        }
        foreach ($this->groups as $group) {
            $report[$group] = [
                'coin' => null,
                'exchange_rate' => null,
                'group_id' => $group,
                'transfer_count' => 0,
                'transfer_amount' => null,
                'transfer_price' => Dec::create(0),
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
                    'transfer_count' => 0,
                    'transfer_amount' => Dec::create(0),
                    'transfer_price' => Dec::create(0),
                ];
            }
        }
        return $report;
    }

    protected function countTransfers($from, $to)
    {
        return app()->make(TransferRepo::class)
            ->queryTransfer([
                ['created_at', '>=', $from],
                ['created_at', '<', $to]
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
            if (in_array($key, ['transfer_price'])) {
                $data = formatted_price($data);
            }
            if (in_array($key, ['transfer_amount'])) {
                $data = formatted_coin_amount($data, data_get($row, 'coin'));
            }
            $result[] = [$index, $data];
        }
        return $result;
    }
}
