<?php

namespace App\Repos\DB;

use App\Models\{
    WfpayAccount,
};

class WfpayAccountRepo implements \App\Repos\Interfaces\WfpayAccountRepo
{
    public function __construct(
        WfpayAccount $wfpay_account
    ) {
        $this->wfpay_account = $wfpay_account;
    }

    public function find($id)
    {
        return $this->wfpay_account->find($id);
    }

    public function findForUpdate($id)
    {
        return $this->wfpay_account
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function findOrFail($id)
    {
        return $this->wfpay_account->findOrFail($id);
    }

    public function update(WfpayAccount $wfpay_account, array $values)
    {
        return $wfpay_account->update($values);
    }

    public function create(array $values)
    {
        return $this->wfpay_account->create($values);
    }

    public function get($active_only = true)
    {
        return $this->wfpay_account
            ->when($active_only, function($query, $active_only){
                return $query->where('is_active', true);
            })
            ->get();
    }

    public function getByRank($active_only = true)
    {
        return $this->wfpay_account
            ->when($active_only, function($query, $active_only){
                return $query->where('is_active', true);
            })
            ->orderBy('rank', 'desc')
            ->get();
    }

    public function getByTransferRank($active_only = true)
    {
        return $this->wfpay_account
            ->when($active_only, function($query, $active_only){
                return $query->where('is_active', true);
            })
            ->orderBy('transfer_rank', 'desc')
            ->get();
    }

    public function getByUsedAt($active_only = true)
    {
        return $this->wfpay_account
            ->when($active_only, function($query, $active_only){
                return $query->where('is_active', true);
            })
            ->orderBy('used_at', 'asc')
            ->orderBy('rank', 'desc')
            ->get();
    }
}
