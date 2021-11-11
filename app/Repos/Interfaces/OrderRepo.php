<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;
use Carbon\Carbon;

interface OrderRepo
{
    public function find($id);
    public function findOrFail($id);
    public function findForUpdate($id);
    public function create($values);
    public function update($order, $values);
    public function getUserOrders(
        $user,
        $status = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    );
    public function getUserSellOrders($user, $status = null);
    public function getUserBuyOrdersCount($user, $status = null);
    public function getUserSellOrdersCount($user, $status = null);
    public function getUserOrdersCount($user, $status = null);
    public function getUserAveragePayTime($user);
    public function getUserAverageReleaseTime($user);
    public function queryOrder($where = [], $keyword = null, $user_id = null);
    public function getExpiredOrders();
}
