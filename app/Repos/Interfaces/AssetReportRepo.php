<?php

namespace App\Repos\Interfaces;

interface AssetReportRepo
{
    public function find($date, $currency, $agency = null);
    public function getAllByDate($date);
    public function getByDates($currency, array $dates, $agency = null);
    public function getChartData($from, $to, $agency = null);
    public function create($date, $agency, array $values);
}
