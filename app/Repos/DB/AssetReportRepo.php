<?php

namespace App\Repos\DB;

use Illuminate\Database\QueryException;

use App\Models\{
    AssetReport,
};
use App\Exceptions\{
    DuplicateRecordError,
};
use App\Repos\Interfaces\{
    AgencyRepo,
};

class AssetReportRepo implements \App\Repos\Interfaces\AssetReportRepo
{
    public function __construct(AssetReport $AssetReport, AgencyRepo $AgencyRepo) {
        $this->AssetReport = $AssetReport;
        $this->AgencyRepo = $AgencyRepo;
        $this->currencies = config('core.currency.all');
    }

    public function find($date, $currency, $agency = null) {
        return $this->AssetReport
            ->where('date', $date)
            ->where('currency', $currency)
            ->where('agency_id', data_get($agency, 'id', $agency))
            ->first();
    }

    public function getAllByDate($date)
    {
        foreach ($this->currencies as $currency) {
            $system_report[$currency] = $this->find($date, $currency, null);
        }
        $agencies = $this->AgencyRepo
            ->getAll();
        foreach ($agencies as $agency) {
            foreach ($this->currencies as $currency) {
                $agency_report[$agency->id][$currency] = $this->find($date, $currency, $agency);
            }
        }
        return [
            'system' => $system_report,
            'agency' => $agency_report,
        ];
    }

    public function getByDates($currency, array $dates, $agency = null)
    {
        $data = [];
        foreach ($dates as $date) {
            $data[$date] = $this->find($date, $currency, $agency);
        }
        return $data;
    }

    public function getChartData($from, $to, $agency = null)
    {
        $dates = date_ticks($from, $to);

        $chart_format_data = function($rate, $dates, $key) {
            $result = [];
            foreach ($dates as $index => $date) {
                $data = formatted_price(data_get($rate, "{$date}.{$key}", 0));
                $result[] = [$index, $data];
            }
            return $result;
        };
        $attributes = ['unit_price', 'balance'];
        $data = [];
        foreach ($this->currencies as $currency) {
            $currency_data = $this->getByDates($currency, $dates, $agency);
            foreach ($attributes as $attribute) {
                $data[$attribute][$currency] = [
                    'label' => $currency,
                    'data' => $chart_format_data($currency_data, $dates, $attribute)
                ];
            }
            $data['trade_amount'][$currency] = [
                [
                    'label' => '交易支出',
                    'data' => $chart_format_data($currency_data, $dates, 'withdraw_amount')
                ],
                [
                    'label' => '交易收入',
                    'data' => $chart_format_data($currency_data, $dates, 'deposit_amount')
                ],
            ];
        }
        return $data;
    }

    public function create($date, $agency, array $values) {
        assert(isset($values['currency']));
        if ($this->find($date, $values['currency'], $agency)) {
            throw new DuplicateRecordError;
        }
        try {
            return $this->AssetReport->create(array_merge([
                'date' => $date,
                'agency_id' => data_get($agency, 'id', $agency),
            ], $values));
        } catch (QueryException $e) {
            throw new DuplicateRecordError;
        }
    }
}
