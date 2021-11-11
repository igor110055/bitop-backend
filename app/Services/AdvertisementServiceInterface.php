<?php

namespace App\Services;

use App\Models\{
    Advertisement,
    User,
};

interface AdvertisementServiceInterface
{
    public function getPriceSpreadPercentage(User $user, $type, $coin, $currency, $unit_price);
    public function preview(User $user, $type, $coin, $currency, $unit_price, $amount);
    public function make(User $user, $values, array $payables);
    public function deactivate(User $user, Advertisement $advertisement);
    public function delete(User $user, Advertisement $advertisement);
}
