<?php

namespace App\Repos\DB;

use App\Models\{
    WalletLog,
};

class WalletLogRepo implements \App\Repos\Interfaces\WalletLogRepo
{
    protected $log;

    public function __construct(WalletLog $log)
    {
        $this->log = $log;
    }

    public function findByWalletIdType(string $wallet_id, string $type)
    {
        assert(in_array($type, WalletLog::TYPES));
        return $this->log
            ->where('wallet_id', $wallet_id)
            ->where('type', $type)
            ->first();
    }

    public function create(array $values)
    {
        return $this->log->create($values);
    }
}
