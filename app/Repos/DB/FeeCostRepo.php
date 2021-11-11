<?php

namespace App\Repos\DB;

use App\Models\FeeCost;

class FeeCostRepo implements \App\Repos\Interfaces\FeeCostRepo
{
    public function __construct(FeeCost $cost)
    {
        $this->cost = $cost;
    }

    public function find(string $id)
    {
        return $this->cost->find($id);
    }

    public function findOrFail(string $id)
    {
        return $this->cost->findOrFail($id);
    }

    public function create(array $values)
    {
        return $this->cost->create($values);
    }

    public function getLatest(string $coin)
    {
        return $this->cost
            ->where('coin', $coin)
            ->latest()
            ->first();
    }
}
