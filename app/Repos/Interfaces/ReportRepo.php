<?php

namespace App\Repos\Interfaces;

interface ReportRepo
{
    public function find($date, $agency = null);
    public function getAllByDate($date);
    public function getByDates($agency, array $dates);
    public function getChartData($from, $to, $agency_id = null);
    public function create($date, array $values);
}
