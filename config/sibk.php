<?php

declare(strict_types=1);

return [
    'temporary_password_ttl_hours' => (int) env('SIBK_TEMPORARY_PASSWORD_TTL_HOURS', 24),

    'seed_accounts' => [
        'password' => env('SIBK_SEED_ACCOUNT_PASSWORD'),
    ],

    'integrations' => [
        'dapodik' => [
            'driver' => env('SIBK_DAPODIK_DRIVER', 'unavailable'),
            'allowed_origins' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('SIBK_DAPODIK_ALLOWED_ORIGINS', '')),
            ), static fn (string $origin): bool => $origin !== '')),
            'allow_private_networks' => (bool) env('SIBK_DAPODIK_ALLOW_PRIVATE_NETWORKS', false),
        ],
        'etatib' => [
            'driver' => env('SIBK_ETATIB_DRIVER', 'unavailable'),
            'allowed_origins' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('SIBK_ETATIB_ALLOWED_ORIGINS', '')),
            ), static fn (string $origin): bool => $origin !== '')),
            'allow_private_networks' => (bool) env('SIBK_ETATIB_ALLOW_PRIVATE_NETWORKS', false),
        ],
    ],
];
