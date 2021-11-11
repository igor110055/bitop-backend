<?php

namespace App\Repos\DB;

use App\Models\{
    Bank,
};

class BankRepo implements \App\Repos\Interfaces\BankRepo
{
    protected $bank;

    public function __construct(Bank $bank) {
        $this->bank = $bank;
    }

    public function find($id)
    {
        return $this->bank->find($id);
    }

    public function findOrFail($id)
    {
        return $this->bank->findOrFail($id);
    }

    public function getBankList()
    {
        return $this->bank
            ->where('is_active', true)
            ->orderBy('nationality')
            ->get();
    }

    public function getBankListByNationality(string $nationality)
    {
        return $this->bank
            ->where('is_active', true)
            ->where('nationality', $nationality)
            ->get();
    }

    public function getBankListIdByNationality(string $nationality)
    {
        return $this->bank
            ->where('is_active', true)
            ->where('nationality', $nationality)
            ->get()
            ->pluck('id');
    }
}
