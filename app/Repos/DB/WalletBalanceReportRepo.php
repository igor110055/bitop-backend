<?php

namespace App\Repos\DB;

use Dec\Dec;
use Illuminate\Database\QueryException;
use App\Exceptions\{
    DuplicateRecordError,
};
use App\Models\WalletBalanceReport;

class WalletBalanceReportRepo implements \App\Repos\Interfaces\WalletBalanceReportRepo
{
    public function __construct(
        WalletBalanceRepo $WalletBalanceRepo,
        WalletBalanceReport $WalletBalanceReport
    ) {
        $this->coins = array_keys(config('coin'));
        $this->WalletBalanceRepo = $WalletBalanceRepo;
        $this->Report = $WalletBalanceReport;
        $this->attributes = [
            'exchange_rate',
            'balance',
            'balance_price',
        ];
    }

    public function find($date, string $coin = null)
    {
        $query = $this->Report->where('date', $date);
        if ($coin) {
            $query->where('coin', $coin);
        } else {
            $query->where('coin', null);
        }
        return $query->first();
    }

    public function create($date, array $values)
    {
        if ($this->find(
            $date,
            data_get($values, 'coin'))
        ) {
            throw new DuplicateRecordError;
        }
        try {
            return $this->Report->create(array_merge([
                'date' => $date,
            ], $values));
        } catch (QueryException $e) {
            throw new DuplicateRecordError;
        }
    }

    public function initReport($to)
    {
        $ExchangeRepo = app()->make(CoinExchangeRateRepo::class);
        $total_balance_price = Dec::create(0);
        $report = [];
        foreach ($this->coins as $coin) {
            $balance = $this->WalletBalanceRepo->getBalance($coin);
            $exchange_rate = $ExchangeRepo->getLatest($coin, $to)->price;
            $balance_price = Dec::mul($balance, $exchange_rate);
            $report[$coin] = [
                'coin' => $coin,
                'exchange_rate' => $exchange_rate,
                'balance' => $balance,
                'balance_price' => (string) $balance_price,
            ];
            $total_balance_price = Dec::add($total_balance_price, $balance_price);
        }
        $report['system'] = [
            'coin' => null,
            'exchange_rate' => null,
            'balance' => null,
            'balance_price' => (string) $total_balance_price,
        ];
        return $report;
    }

    public function getAllByDate($date)
    {
        $report = [];
        foreach ($this->coins as $coin) {
            $report[$coin] = $this->find($date, $coin);
        }
        $report['ALL_COINS'] = $this->find($date);
        return $report;
    }

    public function getSpecificByDates(array $dates, string $coin = null)
    {
        $report = [];
        foreach ($dates as $date) {
            $report[$date] = $this->find($date, $coin);
        }
        return $report;
    }

    public function getChartData($from, $to)
    {
        $dates = date_ticks($from, $to);
        $result = [];
        foreach ($this->coins as $coin) {
            $reports = $this->getSpecificByDates($dates, $coin);
            foreach ($this->attributes as $attribute) {
                $result[$attribute][$coin] = [
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
            if (in_array($key, ['balance_price', 'exchange_rate'])) {
                $data = formatted_price($data);
            }
            if (in_array($key, ['balance'])) {
                $data = formatted_coin_amount($data, data_get($row, 'coin'));
            }
            $result[] = [$index, $data];
        }
        return $result;
    }
}
