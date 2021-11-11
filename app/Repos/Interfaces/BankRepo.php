<?php

namespace App\Repos\Interfaces;

interface BankRepo
{
    public function find($id);
    public function findOrFail($id);
    public function getBankList();
    public function getBankListByNationality(string $nationality);
    public function getBankListIdByNationality(string $nationality);
}
