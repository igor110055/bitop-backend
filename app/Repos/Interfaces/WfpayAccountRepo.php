<?php

namespace App\Repos\Interfaces;

use App\Models\{
    WfpayAccount,
};

interface WfpayAccountRepo
{
    public function find($id);
    public function findForUpdate($id);
    public function findOrFail($id);
    public function update(WfpayAccount $wfpayment, array $values);
    public function create(array $values);
    public function get($active_only = true);
    public function getByRank($active_only = true);
    public function getByTransferRank($active_only = true);
    public function getByUsedAt($active_only = true);
}
