<?php

use Gizburdt\Cook\Commands\Ai;
use Gizburdt\Cook\Commands\Backups;
use Gizburdt\Cook\Commands\Base;
use Gizburdt\Cook\Commands\CodeQuality;
use Gizburdt\Cook\Commands\Filament;
use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Operations;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Publish;
use Gizburdt\Cook\Commands\Ui;
use Gizburdt\Cook\CookServiceProvider;

it('extends service provider', function () {
    expect(CookServiceProvider::class)
        ->toExtend(Illuminate\Support\ServiceProvider::class);
});

it('has register method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('register');
});

it('has boot method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('boot');
});

it('has protected methods for publishable groups', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('operations')
        ->toHaveMethod('base')
        ->toHaveMethod('codeQuality')
        ->toHaveMethod('ai')
        ->toHaveMethod('filament')
        ->toHaveMethod('files');
});

it('has all command classes available', function () {
    expect(Install::class)->toBeString()
        ->and(Publish::class)->toBeString()
        ->and(Operations::class)->toBeString()
        ->and(Base::class)->toBeString()
        ->and(CodeQuality::class)->toBeString()
        ->and(Ai::class)->toBeString()
        ->and(Filament::class)->toBeString()
        ->and(Ui::class)->toBeString()
        ->and(Packages::class)->toBeString()
        ->and(Backups::class)->toBeString();
});

it('has operations publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/operations';

    expect(file_exists($basePath.'/config/one-time-operations.php'))->toBeTrue()
        ->and(file_exists($basePath.'/stubs/one-time-operation.stub'))->toBeTrue();
});

it('has base publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/base';

    expect(is_dir($basePath.'/stubs'))->toBeTrue()
        ->and(file_exists($basePath.'/Http/Resources/Resource.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Models/Model.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Models/Pivot.php'))->toBeTrue()
        ->and(is_dir($basePath.'/Models/Concerns'))->toBeTrue()
        ->and(file_exists($basePath.'/Policies/Policy.php'))->toBeTrue();
});

it('has code quality publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/code-quality';

    expect(is_dir($basePath.'/.github'))->toBeTrue()
        ->and(file_exists($basePath.'/config/essentials.php'))->toBeTrue()
        ->and(file_exists($basePath.'/config/insights.php'))->toBeTrue()
        ->and(file_exists($basePath.'/phpstan.neon'))->toBeTrue()
        ->and(file_exists($basePath.'/pint.json'))->toBeTrue()
        ->and(file_exists($basePath.'/rector.php'))->toBeTrue();
});

it('has ai publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/ai';

    expect(is_dir($basePath.'/.ai'))->toBeTrue()
        ->and(is_dir($basePath.'/.claude'))->toBeTrue();
});

it('has filament publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/filament';

    expect(is_dir($basePath.'/Filament'))->toBeTrue();
});

it('has health publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/health';

    expect(file_exists($basePath.'/config/health.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Support/Notifiable.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Support/Notification.php'))->toBeTrue();
});

it('has backups publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/backups';

    expect(file_exists($basePath.'/config/backup.php'))->toBeTrue();
});

it('has protected methods for all publish groups', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('health')
        ->toHaveMethod('failedJobMonitor')
        ->toHaveMethod('backups');
});

it('has health command class available', function () {
    expect(Gizburdt\Cook\Commands\Health::class)->toBeString()
        ->and(Gizburdt\Cook\Commands\FailedJobMonitor::class)->toBeString();
});

it('health config has result stores configured', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/config/health.php');

    expect($content)
        ->toContain('result_stores')
        ->and($content)->toContain('JsonFileHealthResultStore::class');
});

it('health config has notifications configured', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/config/health.php');

    expect($content)
        ->toContain('notifications')
        ->and($content)->toContain("'enabled' => true")
        ->and($content)->toContain('App\Support\Health\Notifiable::class');
});

it('health config has discord webhook url setting', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/config/health.php');

    expect($content)
        ->toContain("'discord'")
        ->and($content)->toContain("'webhook_url'")
        ->and($content)->toContain('HEALTH_DISCORD_WEBHOOK_URL');
});

it('health notification file has to discord method', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/Support/Notification.php');

    expect($content)
        ->toContain('public function toDiscord()')
        ->and($content)->toContain('DiscordMessage');
});

it('health notifiable file has route notification for discord method', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/Support/Notifiable.php');

    expect($content)
        ->toContain('public function routeNotificationForDiscord()')
        ->and($content)->toContain("config('health.notifications.discord.webhook_url')");
});

it('backup config has backup source settings', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'backup'")
        ->and($content)->toContain("'name'")
        ->and($content)->toContain("'source'")
        ->and($content)->toContain("'destination'");
});

it('backup config has notifications configured for discord', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'notifications'")
        ->and($content)->toContain("'discord'")
        ->and($content)->toContain("'webhook_url'")
        ->and($content)->toContain('BACKUP_DISCORD_WEBHOOK_URL');
});

it('backup config has monitor backups settings', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'monitor_backups'")
        ->and($content)->toContain("'health_checks'");
});

it('backup config has cleanup strategy configured', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'cleanup'")
        ->and($content)->toContain("'strategy'")
        ->and($content)->toContain("'default_strategy'");
});

it('backup config uses backups disk for destination', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'disks'")
        ->and($content)->toContain("'backups'");
});

it('registers composer as singleton', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('register');

    $reflection = new ReflectionMethod(CookServiceProvider::class, 'register');

    expect($reflection->isPublic())->toBeTrue();
});

it('registers all commands in boot method', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'boot');
    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain('Install::class')
        ->and($content)->toContain('Base::class')
        ->and($content)->toContain('Ai::class')
        ->and($content)->toContain('CodeQuality::class')
        ->and($content)->toContain('Operations::class')
        ->and($content)->toContain('Health::class')
        ->and($content)->toContain('FailedJobMonitor::class')
        ->and($content)->toContain('Backups::class')
        ->and($content)->toContain('Filament::class')
        ->and($content)->toContain('Ui::class')
        ->and($content)->toContain('Packages::class');
});

it('has files helper method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('files');
});

it('publishes base group with correct tag', function () {
    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'cook-base'")
        ->and($content)->toContain("'cook-ai'")
        ->and($content)->toContain("'cook-code-quality'")
        ->and($content)->toContain("'cook-operations'")
        ->and($content)->toContain("'cook-health'")
        ->and($content)->toContain("'cook-failed-job-monitor'")
        ->and($content)->toContain("'cook-backups'")
        ->and($content)->toContain("'cook-filament'");
});

it('base method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'base');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'stubs' => 'stubs'")
        ->and($content)->toContain("'Models/Model.php' => 'app/Models/Model.php'")
        ->and($content)->toContain("'Models/Pivot.php' => 'app/Models/Pivot.php'");
});

it('ai method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'ai');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'.ai' => '.ai'")
        ->and($content)->toContain("'.claude' => '.claude'");
});

it('code quality method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'codeQuality');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'.github' => '.github'")
        ->and($content)->toContain("'phpstan.neon' => 'phpstan.neon'")
        ->and($content)->toContain("'pint.json' => 'pint.json'")
        ->and($content)->toContain("'rector.php' => 'rector.php'");
});

it('operations method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'operations');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'config/one-time-operations.php' => 'config/one-time-operations.php'")
        ->and($content)->toContain("'stubs/one-time-operation.stub' => 'stubs/one-time-operation.stub'");
});

it('health method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'health');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'config/health.php' => 'config/health.php'")
        ->and($content)->toContain("'Support/Notifiable.php' => 'app/Support/Health/Notifiable.php'")
        ->and($content)->toContain("'Support/Notification.php' => 'app/Support/Health/Notification.php'");
});

it('failed job monitor method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'failedJobMonitor');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'config/failed-job-monitor.php' => 'config/failed-job-monitor.php'")
        ->and($content)->toContain("'Support/Notifiable.php' => 'app/Support/FailedJobMonitor/Notifiable.php'")
        ->and($content)->toContain("'Support/Notification.php' => 'app/Support/FailedJobMonitor/Notification.php'");
});

it('backups method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'backups');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'config/backup.php' => 'config/backup.php'");
});

it('filament method is protected and returns array', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'filament');

    expect($reflection->isProtected())->toBeTrue();

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain("'Filament' => 'app/Filament'");
});

it('files helper method is protected', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'files');

    expect($reflection->isProtected())->toBeTrue();
});
