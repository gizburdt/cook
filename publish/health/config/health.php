<?php

/**
 * https://github.com/spatie/laravel-health/blob/main/config/health.php
 */

use Awssat\Notifications\Channels\DiscordWebhookChannel;

return [

    'result_stores' => [
        Spatie\Health\ResultStores\JsonFileHealthResultStore::class => [
            'disk' => 'local',
            'path' => 'health.json',
        ],
    ],

    'notifications' => [
        'enabled' => true,

        'notifications' => [
            App\Support\Health\Notification::class => [DiscordWebhookChannel::class],
        ],

        'notifiable' => App\Support\Health\Notifiable::class,

        'throttle_notifications_for_minutes' => 60,
        'throttle_notifications_key' => 'health:latestNotificationSentAt:',

        'mail' => [
            'to' => 'your@example.com',

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('HEALTH_SLACK_WEBHOOK_URL', ''),
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => env('HEALTH_DISCORD_WEBHOOK_URL', ''),
        ],
    ],

    'oh_dear_endpoint' => [
        'enabled' => false,
        'always_send_fresh_results' => true,
        'secret' => env('OH_DEAR_HEALTH_CHECK_SECRET'),
        'url' => '/oh-dear-health-check-results',
    ],

    'horizon' => [
        'heartbeat_url' => env('HORIZON_HEARTBEAT_URL'),
    ],

    'schedule' => [
        'heartbeat_url' => env('SCHEDULE_HEARTBEAT_URL'),
    ],

    'theme' => 'light',

    'silence_health_queue_job' => true,

    'json_results_failure_status' => 200,

    'secret_token' => env('HEALTH_SECRET_TOKEN'),

];
