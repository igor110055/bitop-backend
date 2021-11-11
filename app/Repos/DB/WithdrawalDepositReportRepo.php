<?php

namespace App\Repos\DB;

use Dec\Dec;
use App\Models\{
    WithdrawalDepositReport,
};

class WithdrawalDepositReportRepo extends BaseReportRepo implements \App\Repos\Interfaces\WithdrawalDepositReportRepo
{
    public function __construct(WithdrawalDepositReport $WithdrawalDepositReport) {
        parent::__construct();
        $this->Report = $WithdrawalDepositReport;
        $this->attributes = [
            'withdrawal_count',
            'withdrawal_amount',
            'withdrawal_price',
            'deposit_count',
            'deposit_amount',
            'deposit_price',
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
            'withdrawal_count' => $this->countWithdrawals($from, $to),
            'withdrawal_amount' => null,
            'withdrawal_price' => Dec::create(0), # sum
            'deposit_count' => $this->countDeposits($from, $to),
            'deposit_amount' => null,
            'deposit_price' => Dec::create(0), # sum
        ];
        foreach ($this->coins as $coin) {
            $report[$coin] = [
                'coin' => $coin,
                'exchange_rate' => $ExchangeRepo
                ->getLatest($coin, $to)
                ->price,
                'group_id' => null,
                'withdrawal_count' => 0,
                'withdrawal_amount' => Dec::create(0),
                'withdrawal_price' => Dec::create(0),
                'deposit_count' => 0,
                'deposit_amount' => Dec::create(0),
                'deposit_price' => Dec::create(0),
            ];
        }
        foreach ($this->groups as $group) {
            $report[$group] = [
                'coin' => null,
                'exchange_rate' => null,
                'group_id' => $group,
                'withdrawal_count' => 0,
                'withdrawal_amount' => null,
                'withdrawal_price' => Dec::create(0),
                'deposit_count' => 0,
                'deposit_amount' => null,
                'deposit_price' => Dec::create(0),
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
                    'withdrawal_count' => 0,
                    'withdrawal_amount' => Dec::create(0),
                    'withdrawal_price' => Dec::create(0),
                    'deposit_count' => 0,
                    'deposit_amount' => Dec::create(0),
                    'deposit_price' => Dec::create(0),
                ];
            }
        }
        return $report;
    }

    protected function countWithdrawals($from, $to)
    {
        return app()->make(WithdrawalRepo::class)
            ->queryWithdrawal([
                ['submitted_confirmed_at', '>=', $from],
                ['submitted_confirmed_at', '<', $to]
            ])->count();
    }

    protected function countDeposits($from, $to)
    {
        return app()->make(DepositRepo::class)
            ->queryDeposit([
                ['confirmed_at', '>=', $from],
                ['confirmed_at', '<', $to]
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
            if (in_array($key, ['withdrawal_price', 'deposit_price'])) {
                $data = formatted_price($data);
            }
            if (in_array($key, ['withdrawal_amount', 'deposit_amount'])) {
                $data = formatted_coin_amount($data, data_get($row, 'coin'));
            }
            $result[] = [$index, $data];
        }
        return $result;
    }
}
