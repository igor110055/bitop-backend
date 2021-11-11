<?php

namespace App\Repos\DB;

use App\Models\{
    WalletManipulation,
};

class WalletManipulationRepo implements \App\Repos\Interfaces\WalletManipulationRepo
{
    protected $manipulate;

    public function __construct(WalletManipulation $manipulate)
    {
        $this->manipulate = $manipulate;
    }

    public function findByWalletIdType(string $wallet_id, string $type)
    {
        assert(in_array($type, WalletManipulation::TYPES));
        return $this->manipulate
            ->where('wallet_id', $wallet_id)
            ->where('type', $type)
            ->first();
    }

    public function findByTransaction(string $transaction)
    {
        return $this->manipulate
            ->where('transaction', $transaction)
            ->first();
    }

    public function create(array $values)
    {
        return $this->manipulate->create($values);
    }

    public function updateCallbackResponse(WalletManipulation $manipulate, array $values)
    {
        if ($this->manipulate
            ->where('id', $manipulate->id)
            ->update([
                'callback_response' => $values,
            ]) !== 1) {
            throw new \Exception;
        }
    }
}
