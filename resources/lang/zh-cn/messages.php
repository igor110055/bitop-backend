<?php

return [
    'user' => [
        'auth_status' => [
            'all' => '全部',
            'processing' => '待审核',
            'passed' => '已通过',
            'rejected' => '拒绝',
            'unauthenticated' => '未验证',
        ],
        'role' => [
            'admin' => 'Admin',
            'assistant' => 'Assistant',
            'viewer' => 'Viewer',
        ],
        'lock_type' => [
            'login' => '密码连续错误，禁止登入',
            'security-code' => '安全码连续错误，禁止登入',
            'admin' => '管理员锁定，禁止登入',
            'backend-login-password' => '后台密码连续错误，禁止登入',
            'backend-login-2fa' => '后台二步骤验证连续错误，禁止登入',
            'transfer' => '禁止转帐',
            'withdrawal' => '禁止提现',
        ],
        'log_message' => [
            'log-in' => '登入',
            'log-in-lock' => '连续密码错误，被禁止登入',
            'log-in-unlock' => '禁止登入解锁',
            'password-fail' => '输入密码错误',
            'password-success' => '输入正确密码',
            'security-code-lock' => '连续安全码错误，被禁止登入',
            'security-code-unlock' => '禁止登入解锁',
            'security-code-fail' => '输入错误的安全码',
            'security-code-success' => '输入正确的安全码',
            'admin-log-in' => '登入后台',
            'admin-log-in-password-fail' => '后台输入错误密码',
            'admin-log-in-2fa-fail' => '后台输入错误二步骤验证码',
            'admin-log-in-lock' => '被禁止登入',
            'admin-log-in-unlock' => '禁止登入解锁',
        ],
    ],
    'asset_transaction' => [
        'types' => [
            'sell-order' => '出售',
            'buy-order' => '购买',
            'manual-deposit' => '手动充值',
            'manual-withdrawal' => '手动提领',
        ],
    ],
    'advertisement' => [
        'status' => [
            'available' => '上架中',
            'completed' => '已完成',
            'unavailable' => '下架中',
            'deleted' => '已删除',
        ],
    ],
    'order' => [
        'status' => [
            'processing' => '进行中',
            'completed' => '已完成',
            'canceled' => '已取消',
            'claimed' => '待确认',
        ],
    ],
    'fee_setting' => [
        'types' => [
            'order' => '订单出售手续费',
            'withdrawal' => '提领手续费',
        ],
    ],
    'limitation' => [
        'types' => [
            'withdrawal' => '提领限额',
        ],
    ],
    'transaction' => [
        'types' => [
            'transfer-in' => '划转',
            'transfer-out' => '划转',
            'sell-order' => '售出',
            'buy-order' => '购入',
            'order-fee' => '订单手续费',
            'fee-share' => '手续费分润',
            'manual-deposit' => '手动充值',
            'manual-withdrawal' => '手动提领',
            'wallet-deposit' => '充值',
            'wallet-withdrawal' => '提领',
            'withdrawal-fee' => '提领手续费',
        ],
        'des_prefix' => [
            'transfer-in' => '划转自 ',
            'transfer-out' => '划转给 ',
            'sell-order' => '售出，订单：',
            'buy-order' => '购入，订单：',
            'order-fee' => '订单手续费，订单：',
            'fee-share' => '手续费分润，订单：',
            'manual-deposit' => '手动充值，操作人：',
            'manual-withdrawal' => '手动提领，操作人：',
            'wallet-deposit' => '充值，帐号：',
            'wallet-withdrawal' => '提领，帐号：',
            'withdrawal-fee' => '提领手续费，自帐号：',
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
            App\Models\Authentication::REASON_ID_FILE_MISSING => '缺少身份验证档案',
            App\Models\Authentication::REASON_ID_FILE_INSUFFICIENT => '提供的档案不足以用于身分验证',
            App\Models\Authentication::REASON_ID_FILE_UNIDENTIFIABLE => '身分验证档案无法辨识',
            App\Models\Authentication::REASON_ID_NOT_MATCHED => '身分证号与档案不符合',
            App\Models\Authentication::REASON_NAME_NOT_MATCHED => '姓名与档案不符合',
            App\Models\Authentication::REASON_INVALID_NAMES => '无效的姓名',
            App\Models\Authentication::REASON_INVALID_USERNAME => '不符规定的显示名称',
            App\Models\Authentication::REASON_USERNAME_EXISTED => '显示名称已被其他用户注册',
        ],
    ],
    'bank_account' => [
        'status' => [
            App\Models\BankAccount::STATUS_ACTIVE => '使用中',
            App\Models\BankAccount::STATUS_PENDING => '待审核',
            App\Models\BankAccount::STATUS_DELETED => '已删除',
        ],
        'reject_reasons' => [
            App\Models\BankAccount::REASON_NAME_NOT_MATCHED => '户名与实名认证资料不符',
            App\Models\BankAccount::REASON_INVALID_NAME => '无效的户名',
            App\Models\BankAccount::REASON_INVALID_TYPE => '无效的帐户类型',
            App\Models\BankAccount::REASON_INVALID_PROVINCE_NAME => '无效的银行省份',
            App\Models\BankAccount::REASON_INVALID_CITY_NAME => '无效的银行城市',
            App\Models\BankAccount::REASON_INVALID_ACCOUNT => '无效的帐户号码',
        ],
    ],
    'admin_action' => [
        'actions' => [
            App\Models\AdminAction::TYPE_APPROVE_BANK_ACCOUNT => '审核通过',
            App\Models\AdminAction::TYPE_REJECT_BANK_ACCOUNT => '审核不通过',
        ],
    ],
    'merchant' => [
        'exchange_rate' => [
            App\Models\ExchangeRate::TYPE_SYSTEM => '原汇率',
            App\Models\ExchangeRate::TYPE_FIXED => '人工汇率',
            App\Models\ExchangeRate::TYPE_FLOATING => '即时汇率调整',
            App\Models\ExchangeRate::TYPE_DIFF => '买卖汇差',
        ],
    ],

];

