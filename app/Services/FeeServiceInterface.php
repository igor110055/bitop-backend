<?php

namespace App\Services;

use App\Models\{
    Group,
    User,
};

interface FeeServiceInterface
{
    public function getActiveSettings(string $coin, string $type, $applicable);
    public function getFee(string $type, $subject, string $coin, $coin_amount);
    public function getMatchedSetting($amount, $fee_settings);
    public function getFeeShares(string $coin, string $amount, User $user);
    public function updateFeeCost();
    public function getWithdrawalFee($coin, $applicable = null);
}
