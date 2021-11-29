<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;
use Carbon\Carbon;
use App\Models\{
    Order,
    User,
    Wftransfer,
};

interface WftranferRepo
{
    public function find($id);
    public function findForUpdate($id);
    public function findOrFail($id);
    public function findByRemoteId(string $remote_id);
    public function update(Wftransfer $wftransfer, array $values);
    public function createByOrder(Order $order);
    public function getTheLatestByOrder(Order $order);
}
