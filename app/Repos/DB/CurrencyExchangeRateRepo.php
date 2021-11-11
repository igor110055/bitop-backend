<?php

namespace App\Repos\DB;

use Dec\Dec;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\{
    CurrencyExchangeRate,
};

class CurrencyExchangeRateRepo implements \App\Repos\Interfaces\CurrencyExchangeRateRepo
{
    protected $exchange_rate;

    public function __construct(CurrencyExchangeRate $exchange_rate) {
        $this->exchange_rate = $exchange_rate;
        $this->currencies = config('core.currency.all');
        $this->price_types = CurrencyExchangeRate::PRICE_TYPES;
    }

    public function find($id)
    {
        return $this->exchange_rate->find($id);
    }

    public function findOrFail($id)
    {
        return $this->exchange_rate->findOrFail($id);
    }

    public function create($currency, $bid, $ask, $group = null)
    {
        $scale = config('core.currency.rate_scale');
        $mid = Dec::add($bid, $ask)->div(2, $scale);
        return $this->exchange_rate->create([
            'group_id' => data_get($group, 'id', $group),
            'currency' => $currency,
            'bid' => $bid,
            'ask' => $ask,
            'mid' => $mid,
        ])->fresh();
    }

    public function getLatest($currency, $group = null, $before = null)
    {
        assert(in_array($currency, $this->currencies));

        if ($group) {
            $rate = $this->exchange_rate
                ->where('currency', $currency)
                ->where('group_id', data_get($group, 'id'))
                ->when($before, function($query, $before) {
                    return $query->where('created_at', '<', $before);
                })
                ->orderBy('id', 'desc')
                ->first();
            if ($rate) {
                return $rate;
            }
        }

        return $this->exchange_rate
            ->where('currency', $currency)
            ->whereNull('group_id')
            ->when($before, function($query, $before) {
                return $query->where('created_at', '<', $before);
            })
            ->orderBy('id', 'desc')
            ->firstOrFail();
    }

    public function getAllByDate($date, $group = null)
    {
        $timezone = config('core.timezone.default');
        $before = Carbon::parse($date, $timezone)->addDay();
        $result = [];
        foreach ($this->currencies as $currency) {
            try {
                $result[$currency] = $this->getLatest($currency, $group, $before);
            } catch (ModelNotFoundException $e) {
                $result[$currency] = null;
            }
        }
        return $result;
    }

    public function getByDates($currency, array $dates, $group = null)
    {
        $timezone = config('core.timezone.default');
        $data = [];
        foreach ($dates as $date) {
            $before = Carbon::parse($date, $timezone)->addDay();
            try {
                $data[$date] = $this->getLatest($currency, $group, $before);
            } catch (ModelNotFoundException $e) {
                $data[$date] = null;
            }
        }
        return $data;
    }

    public function getChartData($from, $to)
    {
        $dates = date_ticks($from, $to);

        $chart_format_data = function($rate, $dates, $key) {
            $result = [];
            foreach ($dates as $index => $date) {
                $result[] = [$index, data_get($rate, "{$date}.{$key}", 0)];
            }
            return $result;
        };

        $data = [];
        foreach ($this->currencies as $currency) {
            $currency_data = $this->getByDates($currency, $dates);
            foreach ($this->price_types as $price_type) {
                $data[$currency][] = [
                    'label' => $price_type,
                    'data' => $chart_format_data($currency_data, $dates, $price_type)
                ];
            }
        }
        return $data;
    }
}
