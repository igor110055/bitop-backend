<?php

/* WARNING
 * -------------------------------------------------------------------------
 * Dont use 'system' in the key field, which represents the name of the coin
 * -------------------------------------------------------------------------
 */
return [
    "USDT-ERC20" => [
        'icon' => config('app.url')."/asset/icon/coin/usdt.svg",
        'network' => 'ERC20',
        'has_tag' => false,
        'tag_name' => null,
        'confirmation' => 10,
        'decimal' => 6,
        'fee_decimal' => 2,
        'checksummable' => true,
        'base' => 'USDT',
        'fee_coin' => 'ETH',
    ],
    "USDT-TRC20" => [
        'icon' => config('app.url')."/asset/icon/coin/usdt-trc20.svg",
        'network' => 'TRC20',
        'has_tag' => false,
        'tag_name' => null,
        'confirmation' => 60,
        'decimal' => 6,
        'fee_decimal' => 2,
        'checksummable' => false,
        'base' => 'USDT',
        'fee_coin' => 'TRX',
    ],
    "BTC" => [
        'icon' => config('app.url')."/asset/icon/coin/btc.svg",
        'network' => null,
        'has_tag' => false,
        'tag_name' => null,
        'confirmation' => 10,
        'decimal' => 8,
        'fee_decimal' => 8,
        'checksummable' => false,
    ],
    "ETH" => [
        'icon' => config('app.url')."/asset/icon/coin/eth.svg",
        'network' => null,
        'has_tag' => false,
        'tag_name' => null,
        'confirmation' => 3,
        'decimal' => 18,
        'fee_decimal' => 6,
        'min_threshold' => 0.5,
        'checksummable' => true,
    ],
    "TRX" => [
        'icon' => config('app.url')."/asset/icon/coin/trx.svg",
        'network' => null,
        'has_tag' => false,
        'tag_name' => null,
        'confirmation' => 60,
        'decimal' => 6,
        'fee_decimal' => 2,
        'min_threshold' => 200,
        'checksummable' => false,
    ],
];
