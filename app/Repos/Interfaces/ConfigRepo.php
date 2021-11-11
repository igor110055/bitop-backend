<?php

namespace App\Repos\Interfaces;

use App\Models\Config;

interface ConfigRepo
{
    public function find(string $id);
    public function findOrFail(string $id);
    public function update(Config $config, array $values);
    public function create(string $attribute, array $values);
    public function get(string $attribute, string $param = null);
    public function getActive(string $attribute);
}
