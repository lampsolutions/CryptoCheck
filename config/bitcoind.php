<?php

return [
    'BTC' => [
        'scheme'   => 'http',
        'host'     => env('BTCD_HOST'),
        'port'     => env('BTCD_RPC_PORT'),
        'user'     => env('BTCD_RPC_USERNAME'),
        'password' => env('BTCD_RPC_PASSWORD'),
        'ca'       => null,
        'zeromq' => [
            'host' => env('BTCD_HOST'),
            'port' => env('BTCD_ZEROMQ_PORT'),
        ],
    ],
    'BCH' => [
        'scheme'   => 'http',
        'host'     => env('BCHD_HOST'),
        'port'     => env('BCHD_RPC_PORT'),
        'user'     => env('BCHD_RPC_USERNAME'),
        'password' => env('BCHD_RPC_PASSWORD'),
        'ca'       => null,
        'zeromq' => [
            'host' => env('BCHD_HOST'),
            'port' => env('BCHD_ZEROMQ_PORT'),
        ],
    ],
    'LTC' => [
        'scheme'   => 'http',
        'host'     => env('LTCD_HOST'),
        'port'     => env('LTCD_RPC_PORT'),
        'user'     => env('LTCD_RPC_USERNAME'),
        'password' => env('LTCD_RPC_PASSWORD'),
        'ca'       => null,
        'zeromq' => [
            'host' => env('LTCD_HOST'),
            'port' => env('LTCD_ZEROMQ_PORT'),
        ],
    ],
    'DASH' => [
        'scheme'   => 'http',
        'host'     => env('DASHD_HOST'),
        'port'     => env('DASHD_RPC_PORT'),
        'user'     => env('DASHD_RPC_USERNAME'),
        'password' => env('DASHD_RPC_PASSWORD'),
        'ca'       => null,
        'zeromq' => [
            'host' => env('DASHD_HOST'),
            'port' => env('DASHD_ZEROMQ_PORT'),
        ],
    ],
];
