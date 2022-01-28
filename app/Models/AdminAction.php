<?php

namespace App\Models;

class AdminAction extends UuidModel
{
    const TYPE_CANCEL_ORDER = 'cancel-order';
    const TYPE_COMPLETE_ORDER = 'complete-order';
    const TYPE_NEW_ORDER_TRANSFER = 'new-order-transfer';
    const TYPE_USER_LOCK = 'user-lock';
    const TYPE_USER_UNLOCK = 'user-unlock';
    const TYPE_UNAVAILABLE_ADVERTISEMENT = 'unavailable-advertisement';
    const TYPE_GROUP_APPLICATION_PASS = 'group-application-pass';
    const TYPE_GROUP_APPLICATION_REJECT = 'group-application-reject';
    const TYPE_DEACTIVATE_TFA = 'deactivate-tfa';
    const TYPE_APPROVE_BANK_ACCOUNT = 'approve-bank-account';
    const TYPE_REJECT_BANK_ACCOUNT = 'reject-bank-account';

    protected $fillable = [
        'admin_id',
        'type',
        'applicable_id',
        'applicable_type',
        'description',
    ];

    public function applicable()
    {
        return $this->morphTo();
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
