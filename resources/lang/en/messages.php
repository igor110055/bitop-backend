<?php

return [
    'user' => [
        'auth_status' => [
            'all' => 'All',
            'processing' => 'Processing',
            'passed' => 'Passed',
            'rejected' => 'Rejected',
            'unauthenticated' => 'Unauthenticated',
        ],
        'role' => [
            'admin' => 'Admin',
            'assistant' => 'Assistant',
            'viewer' => 'Viewer',
        ],
        'lock_type' => [
            'login' => 'blocked due to 3 wrong password tries',
            'security-code' => 'blocked due to 3 wrong security code tries',
            'admin' => 'blocked by admin',
            'backend-login-password' => 'blocked due to 3 backstage wrong password tries',
            'backend-login-2fa' => 'blocked due to 3 backstage wrong 2fa tries',
            'transfer' => 'transfer blocked',
            'withdrawal' => 'withdrawal blocked',
        ],
    ],
    'asset_transaction' => [
        'types' => [
            'sell-order' => '出售',
            'buy-order' => '購買',
            'manual-deposit' => '手動充值',
            'manual-withdrawal' => '手動提領',
        ],
    ],
    'advertisement' => [
        'status' => [
            'available' => 'Available',
            'completed' => 'Completed',
            'unavailable' => 'Unavailable',
            'deleted' => 'Deleted',
        ],
    ],
    'order' => [
        'status' => [
            'processing' => 'Processing',
            'completed' => 'Completed',
            'canceled' => 'Canceled',
            'claimed' => 'Claimed',
        ],
    ],
    'fee_setting' => [
        'types' => [
            'order' => 'Transaction Fee',
            'withdrawal' => 'Withdrawal Fee',
        ],
    ],
    'limitation' => [
        'types' => [
            'withdrawal' => 'Withdrawal Limitation',
        ],
    ],
    'transaction' => [
        'types' => [
            'transfer-in' => 'transfer',
            'transfer-out' => 'transfer',
            'sell-order' => 'sell order',
            'buy-order' => 'buy order',
            'order-fee' => 'order fee',
            'fee-share' => 'fee share',
            'manual-deposit' => 'manual deposit',
            'manual-withdrawal' => 'manual withdrawal',
            'wallet-deposit' => 'wallet deposit',
            'wallet-withdrawal' => 'wallet withdrawal',
            'withdrawal-fee' => 'withdrawal fee',
        ],
        'des_prefix' => [
            'transfer-in' => 'transfer from ',
            'transfer-out' => 'transfer to ',
            'sell-order' => 'sell order; order: ',
            'buy-order' => 'buy order; order: ',
            'order-fee' => 'order fee; order: ',
            'fee-share' => 'fee share; order: ',
            'manual-deposit' => 'manual deposit; operator: ',
            'manual-withdrawal' => 'manual withdrawal; operator: ',
            'wallet-deposit' => 'wallet deposit; account: ',
            'wallet-withdrawal' => 'wallet withdrawal; account: ',
            'withdrawal-fee' => 'withdrawal fee; account: ',
        ],
    ],
    'wallet_balance_transaction' => [
        'types' => [
            'deposit' => 'deposit',
            'withdrawal' => 'withdrawal',
            'payin' => 'payin',
            'payout' => 'payout',
            'approvement' => 'approvement',
            'manual-correction' => 'manual correction',
            'manual-deposit' => 'manual deposit',
            'manual-withdrawal' => 'manual withdrawal',
            'wallet-fee' => 'wallet fee',
            'wallet-fee-correction' => 'wallet fee correction',
        ],
        'des_prefix' => [
            'deposit' => 'Deposit by ',
            'withdrawal' => 'Withdrawal by ',
            'payin' => 'wallet internal payin',
            'payout' => 'wallet internal payout',
            'approvement' => 'wallet internal approvement',
            'manual-correction' => 'Manual correction by ',
            'manual-deposit' => 'External deposit',
            'manual-withdrawal' => 'External withdrawal',
            'wallet-fee' => 'fee for blockchain',
            'wallet-fee-correction' => 'fee correction',
        ],
    ],
    'authentication' => [
        'reject_reasons' => [
            App\Models\Authentication::REASON_ID_FILE_MISSING => 'ID verification files are not provided.',
            App\Models\Authentication::REASON_ID_FILE_INSUFFICIENT => 'Files provided are not sufficent for ID verification.',
            App\Models\Authentication::REASON_ID_FILE_UNIDENTIFIABLE => 'ID verification files provided are unidentifiable.',
            App\Models\Authentication::REASON_ID_NOT_MATCHED => 'ID number provided and files are not matched.',
            App\Models\Authentication::REASON_NAME_NOT_MATCHED => 'Name provided and files are not matched.',
            App\Models\Authentication::REASON_INVALID_NAMES => 'Invalid first name / last name.',
            App\Models\Authentication::REASON_INVALID_USERNAME => 'Invalid display name.',
            App\Models\Authentication::REASON_USERNAME_EXISTED => 'Display name already registered.',
        ],
    ],
    'bank_account' => [
        'status' => [
            App\Models\BankAccount::STATUS_ACTIVE => 'Active',
            App\Models\BankAccount::STATUS_PENDING => 'Under review',
            App\Models\BankAccount::STATUS_DELETED => 'Deleted',
        ],
        'reject_reasons' => [
            App\Models\BankAccount::REASON_NAME_NOT_MATCHED => 'Account name does not match your KYC information.',
            App\Models\BankAccount::REASON_INVALID_NAME => 'Invalid account name.',
            App\Models\BankAccount::REASON_INVALID_TYPE => 'Invalie account type',
            App\Models\BankAccount::REASON_INVALID_PROVINCE_NAME => 'Invalid province name.',
            App\Models\BankAccount::REASON_INVALID_CITY_NAME => 'Invalid city name.',
            App\Models\BankAccount::REASON_INVALID_ACCOUNT => 'Invalid account number.',
        ],
    ],
    'admin_action' => [
        'actions' => [
            App\Models\AdminAction::TYPE_APPROVE_BANK_ACCOUNT => 'Approve',
            App\Models\AdminAction::TYPE_REJECT_BANK_ACCOUNT => 'Reject',
        ],
    ],
];
