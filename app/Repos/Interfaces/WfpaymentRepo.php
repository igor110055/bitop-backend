<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;
use Carbon\Carbon;
use App\Models\{
    Order,
    User,
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
    public function createWithPaymentInfo(array $values);
    public function getPaymentInfo(Wfpayment $wfpayment);
    public function createByOrder(Order $order, $payment_method = 'bank');
    public function getTheLatestByOrder(Order $order);
}
