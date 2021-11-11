<?php

namespace App\Repos\Interfaces;

interface CoinExchangeRateRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create(array $values);
    public function getLatest($coin, $before = null);
    public function getByDates($coin, array $dates);
}
