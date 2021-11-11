<?php

namespace App\Repos\Interfaces;

interface CurrencyExchangeRateRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create($currency, $bid, $ask, $group = null);
    public function getLatest($currency, $group = null, $before = null);
    public function getAllByDate($date, $group = null);
    public function getByDates($currency, array $dates, $group = null);
    public function getChartData($from, $to);
}
