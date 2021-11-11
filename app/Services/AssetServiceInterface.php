<?php

namespace App\Services;

interface AssetServiceInterface
{
    public function deposit(
        $agency,
        $currency,
        string $amount,
        $type,
        $unit_price = null,
        $transactable = null
    );

    public function withdraw(
        $agency,
        $currency,
        string $amount,
        $type,
        $transactable = null
    );

    public function manipulate(
        $asset,
        $user,
        $type,
        $amount,
        $unit_price,
        $note
    );
}
