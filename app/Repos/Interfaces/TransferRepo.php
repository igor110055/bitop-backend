<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;

use App\Models\{
    Transfer,
    User,
};

interface TransferRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create(array $values);
    public function queryTransfer($where = []);
    public function cancel(Transfer $transfer);
    public function confirm(Transfer $transfer);
    public function getExpiredTransfers();
    public function getTransaction(Transfer $transfer, string $type);
    public function getUserTransfers(
        User $user,
        string $coin = null,
        string $side,
        $search_user = null,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $limit,
        int $offset
    );
}
