<?php

/**
 * https://github.com/spatie/laravel-health/blob/main/config/health.php
 */

use App\Support\Health\Notifiable;
use App\Support\Health\Notification;
use Awssat\Notifications\Channels\DiscordWebhookChannel;
use Spatie\Health\ResultStores\JsonFileHealthResultStore;

return [

    'result_stores' => [
        JsonFileHealthResultStore::class => [
            'disk' => 'local',
            'path' => 'health.json',
        ],
    ],

    'notifications' => [
        'enabled' => true,

        'notifications' => [
            Notification::class => [DiscordWebhookChannel::class],
        ],

        'notifiable' => Notifiable::class,

        'throttle_notifications_for_minutes' => 120,
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
        'url' => '/health.json',
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
