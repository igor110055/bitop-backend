<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_SES_REGION', 'us-east-1'),
    ],

    'sns' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'currencylayer' => [
        'link' => 'http://apilayer.net/api/live',
        'key' => env('CURRENCYLAYER_KEY'),
    ],

    'tw-bank' => [
        'csv-file' => 'https://rate.bot.com.tw/xrt/flcsv/0/day',
    ],

    'huobi' => [
        'usdt-cny' => 'https://otc-api-hk.eiijo.cn/v1/data/trade-market?coinId=2&currency=1&tradeType=buy&currPage=1&payMethod=0&country=37&blockType=block&online=1&range=0&amount=',
    ],

    'coinmarketcap' => [
        'link' => 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest',
        'key' => env('COINMARKETCAP_KEY'),
    ],

    'nexmo' => [
        'key' => env('NEXMO_KEY'),
        'secret' => env('NEXMO_SECRET'),
        'sms_from' => 'NEXMO',
    ],

    'wallet' => [
        'env' => env('WALLET_SERVICE_ENV'),
        'account' => env('WALLET_SERVICE_ACCOUNT'),
        'token' => env('WALLET_SERVICE_TOKEN'),
        'key' => env('WALLET_SERVICE_KEY'),
        'testnet' => 'https://wallet2-dev.potentia.tech',
        'mainnet' => 'https://wallet2.potentia.tech',
        'api' => [
            'time' => '/api/now',
            'get_currencies' => '/api/currencies',
        ],
        'callback_proxy_domain' => env('WALLET_CALLBACK_PROXY_DOMAIN'),
        'coin_map' => [
            'BTC' => 'btc',
            'ETH' => 'eth',
            'EOS' => 'eos',
            'TRX' => 'trx',
            'USDT-ERC20' => env('WALLET_SERVICE_ENV') === 'mainnet' ? 'usdt-erc20' : 'bgc21-erc20',
            'USDT-TRC20' => env('WALLET_SERVICE_ENV') === 'mainnet' ? 'usdt-trc20' : 'bgc21-trc20',
        ],
        'reverse_coin_map' => [
            'btc' => 'BTC',
            'eth' => 'ETH',
            'trx' => 'TRX',
            'usdt-erc20' => env('WALLET_SERVICE_ENV') === 'mainnet' ? 'USDT-ERC20' : null,
            'bgc21-erc20' => env('WALLET_SERVICE_ENV') === 'mainnet' ? null : 'USDT-ERC20',
            'usdt-trc20' => env('WALLET_SERVICE_ENV') === 'mainnet' ? 'USDT-TRC20' : null,
            'bgc21-trc20' => env('WALLET_SERVICE_ENV') === 'mainnet' ? null : 'USDT-TRC20',
        ]
    ],

    'captcha' => [
        'key' => env('HCAPTCHA_KEY'),
        'secret' => env('HCAPTCHA_SECRET'),
        'link' => 'https://hcaptcha.com/siteverify',
    ],

   'fcm' => [
       'link' => env('FCM_CLOUD_MESSAGING_URL'),
       'key' => env('FCM_SERVER_KEY'),
       'queue_name' => env('FCM_QUEUE_NAME', 'default'),
       'timeout' => 15, # seconds
    ],
];
