<?php

use Awssat\Notifications\Channels\DiscordWebhookChannel;

return [

    /*
     * The notification that will be sent when a job fails.
     */
    'notification' => App\Support\FailedJobMonitor\Notification::class,

    /*
     * The notifiable to which the notification will be sent. The default
     * notifiable will use the mail and slack configuration specified
     * in this config file.
     */
    'notifiable' => App\Support\FailedJobMonitor\Notifiable::class,

    /*
     * By default notifications are sent for all failures. You can pass a callable to filter
     * out certain notifications. The given callable will receive the notification. If the callable
     * return false, the notification will not be sent.
     */
    'notificationFilter' => null,

    /*
     * The channels to which the notification will be sent.
     */
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
