<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Order,
    Wfpayment,
};

interface WfpaymentRepo
{
    public function find($id);
    public function findForUpdate($id);
    public function findOrFail($id);
    public function findByRemoteId(string $remote_id);
    public function update(Wfpayment $wfpayment, array $values);
    public function create(array $values);
    public function createRemote(Wfpayment $wfpayment);
    public function createByOrder(Order $order, $payment_method = 'bank');
    public function getTheLatestByOrder(Order $order);
    public function getAllPending();
}
