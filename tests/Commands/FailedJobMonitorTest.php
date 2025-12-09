<?php

it('has failed job monitor publishable files', function () {
    $basePath = dirname(__DIR__, 2).'/publish/failed-job-monitor';

    expect(file_exists($basePath.'/Support/Notifiable.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Support/Notification.php'))->toBeTrue()
        ->and(file_exists($basePath.'/config/failed-job-monitor.php'))->toBeTrue();
});

it('config uses discord webhook channel class', function () {
    $content = file_get_contents(dirname(__DIR__, 2).'/publish/failed-job-monitor/config/failed-job-monitor.php');

    expect($content)
        ->toContain("'channels'")
        ->and($content)->toContain('DiscordWebhookChannel::class');
});

it('config has notification class configured', function () {
    $content = file_get_contents(dirname(__DIR__, 2).'/publish/failed-job-monitor/config/failed-job-monitor.php');

    expect($content)
        ->toContain("'notification'")
        ->and($content)->toContain('App\Support\FailedJobMonitor\Notification');
});

it('config has notifiable class configured', function () {
    $content = file_get_contents(dirname(__DIR__, 2).'/publish/failed-job-monitor/config/failed-job-monitor.php');

    expect($content)
        ->toContain("'notifiable'")
        ->and($content)->toContain('App\Support\FailedJobMonitor\Notifiable');
});

it('config has discord webhook url setting', function () {
    $content = file_get_contents(dirname(__DIR__, 2).'/publish/failed-job-monitor/config/failed-job-monitor.php');

    expect($content)
        ->toContain("'discord'")
        ->and($content)->toContain("'webhook_url'")
        ->and($content)->toContain('FAILED_JOB_DISCORD_WEBHOOK_URL');
});

it('notification file has to discord method', function () {
    $content = file_get_contents(dirname(__DIR__, 2).'/publish/failed-job-monitor/Support/Notification.php');

    expect($content)
        ->toContain('public function toDiscord()')
        ->and($content)->toContain('DiscordMessage');
});

it('notifiable file has route notification for discord method', function () {
    $content = file_get_contents(dirname(__DIR__, 2).'/publish/failed-job-monitor/Support/Notifiable.php');

    expect($content)
        ->toContain('public function routeNotificationForDiscord()')
        ->and($content)->toContain("config('failed-job-monitor.discord.webhook_url')");
});
