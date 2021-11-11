<?php

namespace App\Services;

use App\Models\{
    User,
    Transfer,
    SystemAction,
};

interface TransferServiceInterface
{
    public function make(
        User $src_user,
        User $dst_user,
        string $coin,
        string $amount,
        string $message = null,
        string $memo = null
    );

    public function confirm(Transfer $transfer);
    public function cancel(Transfer $transfer, string $role = SystemAction::class);
}
