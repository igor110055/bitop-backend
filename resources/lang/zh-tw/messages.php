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
            'claimed' => '付款待確認',
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
];
