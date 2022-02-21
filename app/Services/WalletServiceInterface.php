<?php

namespace App\Services;

interface WalletServiceInterface
{
    public function serverTime();
    public function getSupportedCoinList();
    public function getSupportedErrors();
    public function createAddress($coin, $client_id, array $callback);
    public function getAddressValidation(string $coin, string $address);
    public function getWithdrawalStats(string $coin);
    public function getAddress($coin, $address, $tag = null);
    public function getAllAddress($coin);
    public function updateAddressCallback(
        $coin,
        $address,
        $tag = null,
        array $callback
    );
    public function getAllBalance(); // all coin balances in the wallet
    public function getBalanceByCoin($coin); // specific coin balance in the wallet
    public function checkInternalAddress($address, $coin);
    public function withdrawal(
        $coin,
        $address,
        $tag,
        $amount,
        $callback,
        $client_withdrawal_id,
        $is_full_payment = true,
        $dryrun
    );
    public function getAllWithdrawals($coin);
    public function getAllDeposits($coin);
    public function checkDepositCallbackParameter(array $values);
    public function checkWithdrawalResponseParameter(array $values);
    public function verifyRequest(\Illuminate\Http\Request $request, $exception = true) : bool;
    public function verifySignature($content, $signature, $exception = true) : bool;
}
