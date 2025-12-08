<?php

use Awssat\Notifications\Channels\DiscordWebhookChannel;

/**
 * https://github.com/spatie/laravel-failed-job-monitor/blob/main/config/failed-job-monitor.php
 */
return [

    'notification' => App\Support\FailedJobMonitor\Notification::class,

    'notifiable' => App\Support\FailedJobMonitor\Notifiable::class,

    'notificationFilter' => null,

    'channels' => [DiscordWebhookChannel::class],

    'mail' => [
        'to' => ['email@example.com'],
    ],

    'slack' => [
        'webhook_url' => env('FAILED_JOB_SLACK_WEBHOOK_URL'),
    ],

    'discord' => [
        'webhook_url' => env('FAILED_JOB_DISCORD_WEBHOOK_URL'),
    ],

];
