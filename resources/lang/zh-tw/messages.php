<?php

return [
    'user' => [
        'auth_status' => [
            'all' => '全部',
            'processing' => '待審核',
            'passed' => '已通過',
            'rejected' => '拒絕',
            'unauthenticated' => '未驗證',
        ],
        'role' => [
            'admin' => 'Admin',
            'assistant' => 'Assistant',
            'viewer' => 'Viewer',
        ],
        'lock_type' => [
            'login' => '密碼連續錯誤，禁止登入',
            'security-code' => '安全碼連續錯誤，禁止登入',
            'admin' => '管理員鎖定，禁止登入',
            'backend-login-password' => '後台密碼連續錯誤，禁止登入',
            'backend-login-2fa' => '後台二步驟驗證連續錯誤，禁止登入',
            'transfer' => '禁止轉帳',
            'withdrawal' => '禁止提現',
        ],
        'log_message' => [
            'log-in' => '登入',
            'log-in-lock' => '連續密碼錯誤，被禁止登入',
            'log-in-unlock' => '禁止登入解鎖',
            'password-fail' => '輸入密碼錯誤',
            'password-success' => '輸入正確密碼',
            'security-code-lock' => '連續安全碼錯誤，被禁止登入',
            'security-code-unlock' => '禁止登入解鎖',
            'security-code-fail' => '輸入錯誤的安全碼',
            'security-code-success' => '輸入正確的安全碼',
            'admin-log-in' => '登入後台',
            'admin-log-in-password-fail' => '後台輸入錯誤密碼',
            'admin-log-in-2fa-fail' => '後台輸入錯誤二步驟驗證碼',
            'admin-log-in-lock' => '被禁止登入',
            'admin-log-in-unlock' => '禁止登入解鎖',
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
            'available' => '上架中',
            'completed' => '已完成',
            'unavailable' => '下架中',
            'deleted' => '已刪除',
        ],
    ],
    'order' => [
        'status' => [
            'processing' => '進行中',
            'completed' => '已完成',
            'canceled' => '已取消',
            'claimed' => '待確認',
        ],
    ],
    'fee_setting' => [
        'types' => [
            'order' => '訂單出售手續費',
            'withdrawal' => '提領手續費',
        ],
    ],
    'limitation' => [
        'types' => [
            'withdrawal' => '提領限額',
        ],
    ],
    'transaction' => [
        'types' => [
            'transfer-in' => '劃轉',
            'transfer-out' => '劃轉',
            'sell-order' => '售出',
            'buy-order' => '購入',
            'order-fee' => '訂單手續費',
            'fee-share' => '手續費分潤',
            'manual-deposit' => '手動充值',
            'manual-withdrawal' => '手動提領',
            'wallet-deposit' => '充值',
            'wallet-withdrawal' => '提領',
            'withdrawal-fee' => '提領手續費',
        ],
        'des_prefix' => [
            'transfer-in' => '劃轉自 ',
            'transfer-out' => '劃轉給 ',
            'sell-order' => '售出，訂單：',
            'buy-order' => '購入，訂單：',
            'order-fee' => '訂單手續費，訂單：',
            'fee-share' => '手續費分潤，訂單：',
            'manual-deposit' => '手動充值，操作人：',
            'manual-withdrawal' => '手動提領，操作人：',
            'wallet-deposit' => '充值，帳號：',
            'wallet-withdrawal' => '提領，帳號：',
            'withdrawal-fee' => '提領手續費，自帳號：',
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
            App\Models\Authentication::REASON_ID_FILE_MISSING => '缺少身份驗證檔案',
            App\Models\Authentication::REASON_ID_FILE_INSUFFICIENT => '提供的檔案不足以用於身分驗證',
            App\Models\Authentication::REASON_ID_FILE_UNIDENTIFIABLE => '身分驗證檔案無法辨識',
            App\Models\Authentication::REASON_ID_NOT_MATCHED => '身分證號與檔案不符合',
            App\Models\Authentication::REASON_NAME_NOT_MATCHED => '姓名與檔案不符合',
            App\Models\Authentication::REASON_INVALID_NAMES => '無效的姓名',
            App\Models\Authentication::REASON_INVALID_USERNAME => '不符規定的顯示名稱',
            App\Models\Authentication::REASON_USERNAME_EXISTED => '顯示名稱已被其他用戶註冊',
        ],
    ],
    'bank_account' => [
        'status' => [
            App\Models\BankAccount::STATUS_ACTIVE => '使用中',
            App\Models\BankAccount::STATUS_PENDING => '待審核',
            App\Models\BankAccount::STATUS_DELETED => '已刪除',
        ],
        'reject_reasons' => [
            App\Models\BankAccount::REASON_NAME_NOT_MATCHED => '戶名與實名認證資料不符',
            App\Models\BankAccount::REASON_INVALID_NAME => '無效的戶名',
            App\Models\BankAccount::REASON_INVALID_TYPE => '無效的帳戶類型',
            App\Models\BankAccount::REASON_INVALID_PROVINCE_NAME => '無效的銀行省份',
            App\Models\BankAccount::REASON_INVALID_CITY_NAME => '無效的銀行城市',
            App\Models\BankAccount::REASON_INVALID_ACCOUNT => '無效的帳戶號碼',
        ],
    ],
    'admin_action' => [
        'actions' => [
            App\Models\AdminAction::TYPE_APPROVE_BANK_ACCOUNT => '審核通過',
            App\Models\AdminAction::TYPE_REJECT_BANK_ACCOUNT => '審核不通過',
        ],
    ],
    'merchant' => [
        'exchange_rate' => [
            App\Models\ExchangeRate::TYPE_SYSTEM => '原匯率',
            App\Models\ExchangeRate::TYPE_FIXED => '人工匯率',
            App\Models\ExchangeRate::TYPE_FLOATING => '即時匯率調整',
            App\Models\ExchangeRate::TYPE_DIFF => '買賣匯差',
        ],
    ],
];
