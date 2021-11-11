<?php

namespace App\Repos\Interfaces;

interface ManipulationRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create($user, $note = null);
}
