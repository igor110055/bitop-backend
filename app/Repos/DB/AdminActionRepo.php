<?php

namespace App\Repos\DB;

use App\Models\{
    AdminAction,
};

class AdminActionRepo implements \App\Repos\Interfaces\AdminActionRepo
{
    protected $action;

    public function __construct(AdminAction $action) {
        $this->action = $action;
    }

    public function find($id)
    {
        return $this->action->find($id);
    }

    public function findOrFail($id)
    {
        return $this->action->findOrFail($id);
    }

    public function createByApplicable($applicable, $values)
    {
        return $applicable->admin_actions()->create($values);
    }
}
