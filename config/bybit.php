<?php
return [
    'api_key' => env('BYBIT_API_KEY', ''),
    'api_secret' => env('BYBIT_API_SECRET', ''),
    'testnet' => env('BYBIT_TESTNET', false),
    'region' => env('BYBIT_REGION', 'global'),
    'recv_window' => env('BYBIT_RECV_WINDOW', 5000),
    'rsa_private_key' => env('BYBIT_RSA_PRIVATE_KEY', null),
    'rsa_public_key' => env('BYBIT_RSA_PUBLIC_KEY', null),
    'signature' => env('BYBIT_SIGNATURE', 'hmac'),
    'fees' => [
        'spot' => [
            'Non-VIP' => ['maker' => 0.0010, 'taker' => 0.0010],
            'VIP1' => ['maker' => 0.000675, 'taker' => 0.0010],
            'VIP2' => ['maker' => 0.000650, 'taker' => 0.000775],
            'VIP3' => ['maker' => 0.000625, 'taker' => 0.000750],
            'VIP4' => ['maker' => 0.000500, 'taker' => 0.000600],
            'VIP5' => ['maker' => 0.000400, 'taker' => 0.000500],
            'Supreme VIP' => ['maker' => 0.000300, 'taker' => 0.000450],
        ],
        'derivatives' => [
            'Non-VIP' => ['maker' => 0.000400, 'taker' => 0.001000],
        ],
    ]
];