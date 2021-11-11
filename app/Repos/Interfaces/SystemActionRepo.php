<?php

namespace App\Repos\Interfaces;

interface SystemActionRepo
{
    public function find(string $id);
    public function findOrFail(string $id);
    public function createByApplicable($applicable, array $values);
    public function create(array $values);
}
