<?php

namespace App\Repos\Interfaces;

interface FeeCostRepo
{
    public function find(string $id);
    public function findOrFail(string $id);
    public function create(array $values);
    public function getLatest(string $coin);
}
