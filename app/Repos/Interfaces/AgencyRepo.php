<?php

namespace App\Repos\Interfaces;

interface AgencyRepo
{
    public function find($id);
    public function findOrFail($id);
    public function getDefaultAgency();
    public function getDefaultAgencyOrFail();
    public function create($values);
}
