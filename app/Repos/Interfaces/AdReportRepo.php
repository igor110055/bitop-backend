<?php

namespace App\Repos\Interfaces;

interface AdReportRepo
{
    public function find($date, string $coin = null, string $group_id = null);
    public function getAllByDate($date);
    public function getSpecificByDates(array $dates, string $coin = null, string $group_id = null);
    public function create($date, array $values);
    public function initReport($from, $to);
    public function getChartData($from, $to, $group_id = null);
}
