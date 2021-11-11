<?php

namespace App\Repos\DB;

use App\Models\Manipulation;

class ManipulationRepo implements \App\Repos\Interfaces\ManipulationRepo
{
    protected $manipulation;

    public function __construct(Manipulation $manipulation) {
        $this->manipulation = $manipulation;
    }

    public function find($id)
    {
        return $this->manipulation->find($id);
    }

    public function findOrFail($id)
    {
        return $this->manipulation->findOrFail($id);
    }

    public function create($user, $note = null)
    {
        return $this->manipulation
            ->create([
                'user_id' => data_get($user, 'id', $user),
                'note' => $note,
            ])->fresh();
    }
}
