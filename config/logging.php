<?php

use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'stdout'],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/lumen.log'),
            'level' => 'debug',
            // 'tap' => [App\Logging\Formatters\CustomFormatter::class],
            'formatter' => App\Logging\Formatters\CustomFormatter::class,
        ],

        'stdout' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => App\Logging\Formatters\CustomFormatter::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
        ],
    ],
];
