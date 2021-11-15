<?php

return [
    'users' => [
        'password' => [
            'regular_expression' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', # At least one uppercase, one lowercase and one digit
        ],
        'login_timeframe' => 10, //minutes
        'fail-max' => [
            'password-fail' => 3,
            'security-code-fail' => 3,
            'admin-log-in-password-fail' => 3,
            'admin-log-in-2fa-fail' => 3,
        ],
        'user-lock' => [
            'login' => [
                'time' => 3600, # seconds
            ],
            'security-code' => [
                'time' => 86400, # seconds
            ],
            'admin' => [
                'time' => 1500000000, # seconds, total approx 50 yrs
            ],
            'backend-login-password' => [
                'time' => 3600, # seconds
            ],
            'backend-login-2fa' => [
                'time' => 86400, # seconds
            ],
        ],
    ],
    'currency' => [
        'precision' => 16,
        'rate_scale' => 6,
        'scale' => 2,
        'base' => 'CNY',
        'default_exp' => 2,
        'all' => [
            'CNY',
        ],
        'exp' => [              # exponent of base unit
            'CNY' => 2,
        ],
    ],
    'coin' => [
        'precision' => 65,
        'rate_scale' => 6,
        'scale' => 30,
        'all' => [
            'USDT-ERC20',
            'BTC',
            'ETH',
            'TRX',
            'USDT-TRC20',
        ],
        'default_exp' => 6,
        'exp' => [              # exponent for amount
            'USDT-ERC20' => 6,
            'BTC' => 6,
            'ETH' => 6,
            'TRX' => 6,
            'USDT-TRC20' => 6,
        ],
        'require_tag' => [],
    ],
    'nationality' => [
        'CN' => [
            'currency' => ['CNY'],
        ],
    ],
    'verification' => [
        'max_tries' => 3,
        'resend_after' => 60,
        'timeout' => [
            \App\Models\Verification::TYPE_EMAIL => 10,
            \App\Models\Verification::TYPE_MOBILE => 10,
            \App\Models\Verification::TYPE_PASSWORD => 10,
            \App\Models\Verification::TYPE_SECURITY_CODE => 10,
            \App\Models\Verification::TYPE_TRANSFER_CONFIRMATION => 30,
            \App\Models\Verification::TYPE_ORDER_CONFIRMATION => 10,
            \App\Models\Verification::TYPE_WITHDRAWAL_CONFIRMATION => 30,
            \App\Models\Verification::TYPE_RESET_EMAIL => 10,
            \App\Models\Verification::TYPE_RESET_MOBILE => 10,
            \App\Models\Verification::TYPE_DEACTIVATE_TFA => 10,
        ],
        'code' => [
            'length' => [
                \App\Models\Verification::TYPE_EMAIL => 24,
                \App\Models\Verification::TYPE_MOBILE => 6,
                \App\Models\Verification::TYPE_PASSWORD => 24,
                \App\Models\Verification::TYPE_SECURITY_CODE => 24,
                \App\Models\Verification::TYPE_TRANSFER_CONFIRMATION => 24,
                \App\Models\Verification::TYPE_ORDER_CONFIRMATION => 6,
                \App\Models\Verification::TYPE_WITHDRAWAL_CONFIRMATION => 24,
                \App\Models\Verification::TYPE_RESET_EMAIL => 24,
                \App\Models\Verification::TYPE_RESET_MOBILE => 6,
                \App\Models\Verification::TYPE_DEACTIVATE_TFA => 6,
            ],
            'type' => [
                \App\Models\Verification::TYPE_EMAIL => \App\Models\Verification::CODE_TYPE_DIGIT_ALPHA,
                \App\Models\Verification::TYPE_MOBILE => \App\Models\Verification::CODE_TYPE_DIGIT,
                \App\Models\Verification::TYPE_PASSWORD => \App\Models\Verification::CODE_TYPE_DIGIT_ALPHA,
                \App\Models\Verification::TYPE_SECURITY_CODE => \App\Models\Verification::CODE_TYPE_DIGIT_ALPHA,
                \App\Models\Verification::TYPE_TRANSFER_CONFIRMATION => \App\Models\Verification::CODE_TYPE_DIGIT_ALPHA,
                \App\Models\Verification::TYPE_ORDER_CONFIRMATION => \App\Models\Verification::CODE_TYPE_DIGIT,
                \App\Models\Verification::TYPE_WITHDRAWAL_CONFIRMATION => \App\Models\Verification::CODE_TYPE_DIGIT_ALPHA,
                \App\Models\Verification::TYPE_RESET_EMAIL => \App\Models\Verification::CODE_TYPE_DIGIT_ALPHA,
                \App\Models\Verification::TYPE_RESET_MOBILE => \App\Models\Verification::CODE_TYPE_DIGIT,
                \App\Models\Verification::TYPE_DEACTIVATE_TFA => \App\Models\Verification::CODE_TYPE_DIGIT,
            ],
        ],
        'channel' => [
            \App\Models\Verification::TYPE_EMAIL => ['mail'],
            \App\Models\Verification::TYPE_MOBILE => ['sms'],
            \App\Models\Verification::TYPE_PASSWORD => ['mail'],
            \App\Models\Verification::TYPE_SECURITY_CODE => ['mail'],
            \App\Models\Verification::TYPE_TRANSFER_CONFIRMATION => ['mail'],
            \App\Models\Verification::TYPE_ORDER_CONFIRMATION => ['mail'],
            \App\Models\Verification::TYPE_WITHDRAWAL_CONFIRMATION => ['mail'],
            \App\Models\Verification::TYPE_RESET_EMAIL => ['mail'],
            \App\Models\Verification::TYPE_RESET_MOBILE => ['sms'],
            \App\Models\Verification::TYPE_DEACTIVATE_TFA => ['mail'],
        ]
    ],
    'withdrawal' => [
        'timeout' => 30,
        'limit' => [
            'daily' => 100000,
        ],
    ],
    'transfer' => [
        'timeout' => 30,
    ],
    'aws_cloud_storage' => [
        'user_authentication' => ['pre_path_name' => 'userfile'],
    ],
    'log_context_max_length' => 4096,
    'group_invitation' => [
        'expired_time' => 3000, #sec
    ],
    'timezone' => [
        'default' => 'Asia/Taipei',
    ],
    'timezone_utc_offset' => [
        'default' => 8,
    ],
    'locale' => [
        'default' => 'en',
        'all' => [
            'en' => 'en',
            'zh' => 'zh-cn',
            'zh-cn' => 'zh-cn',
            'zh-tw' => 'zh-tw',
        ],
    ],
    'googlechat' => [
        'webhook' => env('GOOGLE_CHAT_WEBHOOK'),
    ],
    'critical_error' => [
        'mail' => env('CRITICAL_ERROR_RECEIVER'),
    ],
    'two_factor_auth' => [
        'withdrawal_limit' => 50,
    ],
    'broadcast' => [
        'user_chunk' => 10,
        'queue' => 'broadcast',
    ],

];
