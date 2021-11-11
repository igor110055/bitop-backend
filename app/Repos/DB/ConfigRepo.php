<?php

namespace App\Repos\DB;

use App\Exceptions\Core\UnknownError;
use App\Models\Config;

class ConfigRepo implements \App\Repos\Interfaces\ConfigRepo
{
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function find(string $id)
    {
        return $this->config->find($id);
    }

    public function findOrFail(string $id)
    {
        return $this->config->findOrFail($id);
    }

    public function update(Config $config, array $values)
    {
        if ($this->config
            ->where('id', $config->id)
            ->update($values) !== 1
        ) {
            throw new UnknownError;
        }
    }

    public function create(string $attribute, array $values)
    {
        $operator = auth()->user();
        if ($this->getActive($attribute)->isNotEmpty()) {
            foreach ($this->getActive($attribute) as $config) {
                $this->update($config, ['is_active' => false]);
            }
        }
        return $this->config->create([
            'attribute' => $attribute,
            'value' => $values,
            'admin_id' => $operator->id,
            'is_active' => true,
        ]);
    }

    public function get(string $attribute, string $param = null)
    {
        $record = $this->config
            ->where('attribute', $attribute)
            ->where('is_active', true)
            ->latest()
            ->first();
        if (!$record) {
            return data_get(Config::DEFAULT, $attribute);
        }
        if (is_null($param)) {
            return data_get($record, 'value');
        }
        return data_get($record, "value.$param");
    }

    public function getActive(string $attribute)
    {
        return $this->config
            ->where('attribute', $attribute)
            ->where('is_active', true)
            ->get();
    }
}
