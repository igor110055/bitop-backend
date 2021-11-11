<?php

namespace App\Repos\DB;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;

use App\Models\{
    Report,
};
use App\Exceptions\{
    DuplicateRecordError,
};
use App\Repos\Interfaces\{
    AgencyRepo,
};

class ReportRepo implements \App\Repos\Interfaces\ReportRepo
{
    public function __construct(
        Report $Report,
        AgencyRepo $AgencyRepo
    ) {
        $this->Report = $Report;
        $this->AgencyRepo = $AgencyRepo;
    }

    public function find($date, $agency = null) {
        return $this->Report
            ->where('date', $date)
            ->where('agency_id', data_get($agency, 'id', $agency))
            ->first();
    }

    public function getAllByDate($date)
    {
        $system_report = $this->find($date, null);
        $agencies = $this->AgencyRepo
            ->getAll();
        foreach ($agencies as $agency) {
            $report = $this->find($date, $agency);
            $agency_report[$agency->id] = is_null($report) ? [] : $report->toArray();
        }
        return [
            'system' => $system_report,
            'agency' => $agency_report,
        ];
    }

    public function getByDates($agency, array $dates)
    {
        $reports = [];
        foreach ($dates as $date) {
            $reports[$date] = $this->find($date, $agency);
        }
        return $reports;
    }

    public function getChartData($from, $to, $agency_id = null)
    {
        $dates = date_ticks($from, $to);
        if ($agency_id) {
            $agency = $this->AgencyRepo->findOrFail($agency_id);
            $agencies[$agency_id] = $agency;
        } else {
            $agencies = $this->AgencyRepo
                ->getAll()
                ->keyBy('id');
        }

        $chart_format_data = function($reports, $dates, $key) {
            $result = [];
            foreach ($dates as $index => $date) {
                $result[] = [$index, data_get($reports, "{$date}.{$key}", 0)];
            }
            return $result;
        };

        $orders_data = [];
        $profits_data = [];

        if (is_null($agency_id)) {
            $system_report = $this->getByDates(null, $dates);
            $orders_data[] = [
                'label' => '全系統',
                'data' => $chart_format_data($system_report, $dates, 'orders')
            ];
            $profits_data[] = [
                'label' => '全系統',
                'data' => $chart_format_data($system_report, $dates, 'profit')
            ];
        }
        foreach ($agencies as $id => $agency) {
            $reports = $this->getByDates($agency, $dates);
            $orders_data[] = [
                'label' => $id,
                'data' => $chart_format_data($reports, $dates, 'orders')
            ];
            $profits_data[] = [
                'label' => $id,
                'data' => $chart_format_data($reports, $dates, 'profit')
            ];
        }
        return ['orders' => $orders_data, 'profits' => $profits_data];
    }

    public function create($date, array $values)
    {
        if ($this->find($date, $values['agency_id'])) {
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
}
