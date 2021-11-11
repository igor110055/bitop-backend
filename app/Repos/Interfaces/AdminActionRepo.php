<?php

namespace App\Repos\Interfaces;

interface AdminActionRepo
{
    public function find($id);
    public function findOrFail($id);
    public function createByApplicable($applicable, $values);
}
