<?php

namespace App\Repos\Interfaces;

interface FeeSettingRepo
{
    public function create(array $values, $applicable = null);
    public function inactivate(string $coin, string $type, $applicable = null);
    public function set(string $coin, string $type, array $ranges, $applicable = null);
    public function setFixed(string $coin, string $type, string $discount_percent, $applicable = null);
    public function get(string $coin, string $type, $applicable = null);
    public function getFixed(string $coin, string $type, $applicable = null);
}
