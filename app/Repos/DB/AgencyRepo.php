<?php

namespace App\Repos\DB;

use App\Models\{
    Agency,
};

class AgencyRepo implements \App\Repos\Interfaces\AgencyRepo
{
    protected $agency;

    public function __construct(Agency $agency) {
        $this->agency = $agency;
    }

    public function getAll()
    {
        return $this->agency->all();
    }

    public function find($id)
    {
        return $this->agency->find($id);
    }

    public function findOrFail($id)
    {
        return $this->agency->findOrFail($id);
    }

    public function getDefaultAgency()
    {
        return $this->find(Agency::DEFAULT_AGENCY_ID);
    }

    public function getDefaultAgencyOrFail()
    {
        return $this->findOrFail(Agency::DEFAULT_AGENCY_ID);
    }

    public function update(Agency $agency, $values)
    {
        return $agency->update($values);
    }

    public function create($values)
    {
        $agency = $this->agency
            ->create([
                'id' => data_get($values, 'id'),
                'name' => data_get($values, 'name'),
            ])->fresh();

        return $agency;
    }
}
