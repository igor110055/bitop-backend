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
            'deleted' => '已刪除',
        ],
    ],
    'order' => [
        'status' => [
            'processing' => '进行中',
            'completed' => '已完成',
            'canceled' => '已取消',
            'claimed' => '付款待确认',
        ],
    ],
    'fee_setting' => [
        'types' => [
            'order' => '订单出售手续费',
            'withdraw' => '提领手续费',
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
            'transfer-out' => '划转自 ',
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
    'authentication' => [
        'reject_reasons' => [
            App\Models\Authentication::REASON_ID_FILE_MISSING => '缺少身份验证档案',
            App\Models\Authentication::REASON_ID_FILE_INSUFFICIENT => '提供的档案不足以用于身分验证',
            App\Models\Authentication::REASON_ID_FILE_UNIDENTIFIABLE => '身分验证档案无法辨识',
            App\Models\Authentication::REASON_ID_NOT_MATCHED => '身分证号与档案不符合',
            App\Models\Authentication::REASON_NAME_NOT_MATCHED => '姓名与档案不符合',
            App\Models\Authentication::REASON_INVALID_NAMES => '无效的姓名',
            App\Models\Authentication::REASON_INVALID_USERNAME => '无效的显示名称',
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
            App\Models\BankAccount::REASON_PHONETIC_NAME_NOT_MATCHED => '英文户名与实名验证资料不符',
            App\Models\BankAccount::REASON_INVALID_NAME => '无效的户名',
            App\Models\BankAccount::REASON_INVALID_PHONETIC_NAME => '无效的英文户名',
            App\Models\BankAccount::REASON_INVALID_BRANCH_NAME => '无效的分行名称',
            App\Models\BankAccount::REASON_INVALID_BRANCH_PHONETIC_NAME => '无效的英文分行名称',
            App\Models\BankAccount::REASON_INVALID_ACCOUNT => '无效的帐户号码',
        ],
    ],
    'admin_action' => [
        'actions' => [
            App\Models\AdminAction::TYPE_APPROVE_BANK_ACCOUNT => '审核通过',
            App\Models\AdminAction::TYPE_REJECT_BANK_ACCOUNT => '审核不通过',
        ],
    ],
];
