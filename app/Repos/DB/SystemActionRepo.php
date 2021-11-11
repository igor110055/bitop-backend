<?php

namespace App\Repos\DB;

use App\Models\{
    SystemAction,
};

class SystemActionRepo implements \App\Repos\Interfaces\SystemActionRepo
{
    protected $action;

    public function __construct(SystemAction $action)
    {
        $this->action = $action;
    }

    public function find(string $id)
    {
        return $this->action->find($id);
    }

    public function findOrFail(string $id)
    {
        return $this->action->findOrFail($id);
    }

    public function createByApplicable($applicable, array $values)
    {
        return $applicable->system_actions()->create($values);
    }

    public function create(array $values)
    {
        return $this->action->create($values);
    }
}
