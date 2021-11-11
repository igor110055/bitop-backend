<?php

namespace App\Repos\Interfaces;

interface WalletBalanceReportRepo
{
    public function find($date, string $coin = null);
    public function create($date, array $values);
    public function initReport($to);
    public function getAllByDate($date);
    public function getSpecificByDates(array $dates, string $coin = null);
    public function getChartData($from, $to);
}
