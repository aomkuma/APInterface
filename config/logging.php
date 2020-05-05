<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [

        'NewCustomer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/NewCustomer.log'),
            'level' => 'debug',
            'days' => 90,
        ],

        'UpdateCustomer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/UpdateCustomer.log'),
            'level' => 'debug',
            'days' => 90,
        ],

        'Event' => [
            'driver' => 'daily',
            'path' => storage_path('logs/Event.log'),
            'level' => 'debug',
            'days' => 90,
        ],

        'Purchase' => [
            'driver' => 'daily',
            'path' => storage_path('logs/Purchase.log'),
            'level' => 'debug',
            'days' => 90,
        ],

        'SubMail' => [
            'driver' => 'daily',
            'path' => storage_path('logs/SubMail.log'),
            'level' => 'debug',
            'days' => 90,
        ],

        'DeleteCustomer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/DeleteCustomer.log'),
            'level' => 'debug',
            'days' => 90,
        ],

        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/InterfaceBraze.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/InterfaceBraze.log'),
            'level' => 'debug',
            'days' => 90,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/InterfaceBraze.log'),
        ],
    ],

];
