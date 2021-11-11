<?php

namespace App\Models;

class SystemAction extends UuidModel
{
    const TYPE_CANCEL_ORDER = 'cancel-order';
    const TYPE_CANCEL_TRANSFER = 'cancel-transfer';
    const TYPE_CANCEL_WITHDRAWAL = 'cancel-withdrawal';

    const TYPE_SUBMIT_WITHDRAWAL = 'submit-withdrawal';

    const TYPE_UNLOCK_USERLOCK = 'unlock-userlock';
    const TYPE_PRUNE_ANNOUNCEMENT_READ_TABLE = 'prune-announcement-read-table';

    const TYPE_UPDATE_COIN_EXCHANGE_RATE = 'update-coin-exchange-rate';
    const TYPE_UPDATE_CURRENCY_EXCHANGE_RATE = 'update-currency-exchange-rate';
    const TYPE_UPDATE_WITHDRAWAL_FEE_COST = 'update-withdrawal-fee-cost';

    protected $fillable = [
        'type',
        'applicable_id',
        'applicable_type',
        'description',
    ];

    public function applicable()
    {
        return $this->morphTo();
    }
}
