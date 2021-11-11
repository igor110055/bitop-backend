<?php

namespace App\Repos\DB;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\{
    CoinExchangeRate,
};

class CoinExchangeRateRepo implements \App\Repos\Interfaces\CoinExchangeRateRepo
{
    protected $exchange_rate;

    public function __construct(CoinExchangeRate $exchange_rate) {
        $this->exchange_rate = $exchange_rate;
        $this->coins = config('coin');
    }

    public function find($id)
    {
        return $this->exchange_rate->find($id);
    }

    public function findOrFail($id)
    {
        return $this->exchange_rate->findOrFail($id);
    }

    public function create(array $values)
    {
        return $this->exchange_rate->create($values);
    }

    public function getLatest($coin, $before = null)
    {
        assert(in_array($coin, array_keys($this->coins)));

        $exchange_rate_coin = data_get($this->coins, "{$coin}.base", $coin);

        return $this->exchange_rate
            ->where('coin', $exchange_rate_coin)
            ->when($before, function($query, $before) {
                return $query->where('created_at', '<', $before);
            })
            ->orderBy('id', 'desc')
            ->firstOrFail();
    }

    public function getByDates($coin, array $dates)
    {
        $timezone = config('core.timezone.default');
        $data = [];
        foreach ($dates as $date) {
            $before = Carbon::parse($date, $timezone)->addDay();
            try {
                $data[$date] = $this->getLatest($coin, $before);
            } catch (ModelNotFoundException $e) {
                $data[$date] = null;
            }
        }
        return $data;
    }

    public function getAllByDate($date)
    {
        $timezone = config('core.timezone.default');
        $before = Carbon::parse($date, $timezone)->addDay();
        $result = [];
        foreach (array_keys($this->coins) as $coin) {
            try {
                $result[$coin] = $this->getLatest($coin, $before);
            } catch (ModelNotFoundException $e) {
                $result[$coin] = null;
            }
        }
        return $result;
    }

    public function getChartData($from, $to)
    {
        $dates = date_ticks($from, $to);
        $result = [];
        foreach (array_keys($this->coins) as $coin) {
            $coin_data = $this->getByDates($coin, $dates);
            foreach (['price'] as $attribute) {
                $result[$attribute][$coin] = [
                    'label' => $coin,
                    'data' => $this->formatData($coin_data, $dates, $attribute)
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
            $result[] = [$index, $data];
        }
        return $result;
    }
}
