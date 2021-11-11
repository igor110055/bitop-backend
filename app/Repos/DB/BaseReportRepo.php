<?php

namespace App\Repos\DB;

use Illuminate\Database\QueryException;
use App\Exceptions\{
    DuplicateRecordError,
};

abstract class BaseReportRepo
{
    public function __construct()
    {
        $this->coins = array_keys(config('coin'));
        $this->groups = app()
            ->make(GroupRepo::class)
            ->getAllGroups()
            ->pluck('id')
            ->toArray();
    }

    public function find($date, string $coin = null, string $group_id = null)
    {
        $query = $this->Report->where('date', $date);
        if ($coin) {
            $query->where('coin', $coin);
        } else {
            $query->where('coin', null);
        }
        if ($group_id) {
            $query->where('group_id', $group_id);
        } else {
            $query->where('group_id', null);
        }
        return $query->first();
    }

    public function getAllByDate($date)
    {
        $report = [];
        foreach ($this->coins as $coin) {
            foreach ($this->groups as $group_id) {
                $report[$coin][$group_id] = $this->find($date, $coin, $group_id);
            }
            $report[$coin]['system'] = $this->find($date, $coin);
        }
        foreach ($this->groups as $group_id) {
            $report['ALL_COINS'][$group_id] = $this->find($date, null, $group_id);
        }
        $report['ALL_COINS']['system'] = $this->find($date);
        return $report;
    }

    public function getSpecificByDates(array $dates, string $coin = null, string $group_id = null)
    {
        $report = [];
        foreach ($dates as $date) {
            $report[$date] = $this->find($date, $coin, $group_id);
        }
        return $report;
    }

    public function create($date, array $values)
    {
        if ($this->find(
            $date,
            data_get($values, 'coin'),
            data_get($values, 'group_id'))
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

    abstract public function getChartData($from, $to);
    abstract public function initReport($from, $to);
}
