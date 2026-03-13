<?php

use App\Support\FailedJobMonitor\Notifiable;
use App\Support\FailedJobMonitor\Notification;
use Awssat\Notifications\Channels\DiscordWebhookChannel;

/**
 * https://github.com/spatie/laravel-failed-job-monitor/blob/main/config/failed-job-monitor.php
 */
return [

    'notification' => Notification::class,

    'notifiable' => Notifiable::class,

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
