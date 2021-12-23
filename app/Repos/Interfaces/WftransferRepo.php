<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Order,
    Wftransfer,
};

interface WftransferRepo
{
    public function find($id);
    public function findForUpdate($id);
    public function findOrFail($id);
    public function findByRemoteId(string $remote_id);
    public function update(Wftransfer $wftransfer, array $values);
    public function createByOrder(Order $order);
    public function getTheLatestByOrder(Order $order);
    public function send(Wftransfer $wftransfer);
    public function getAllPending();
}
