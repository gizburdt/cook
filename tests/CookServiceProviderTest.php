<?php

use Gizburdt\Cook\Commands\Ai;
use Gizburdt\Cook\Commands\Backups;
use Gizburdt\Cook\Commands\Base;
use Gizburdt\Cook\Commands\CodeQuality;
use Gizburdt\Cook\Commands\FailedJobMonitor;
use Gizburdt\Cook\Commands\Filament;
use Gizburdt\Cook\Commands\Health;
use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Operations;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Publish;
use Gizburdt\Cook\Commands\Ui;
use Gizburdt\Cook\CookServiceProvider;
use Illuminate\Support\ServiceProvider;

it('extends service provider', function () {
    expect(CookServiceProvider::class)
        ->toExtend(ServiceProvider::class);
});

it('has register method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('register');
});

it('has boot method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('boot');
});

it('has files helper method on service provider', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('bootPublishes')
        ->toHaveMethod('files');
});

it('every command class declares a publish group', function (string $name, string $expected) {
    expect(commandSource($name))
        ->toContain("public string \$publishGroup = '{$expected}';");
})->with([
    'ai' => ['Ai', 'ai'],
    'base' => ['Base', 'base'],
    'ddd' => ['Ddd', 'ddd'],
    'code-quality' => ['CodeQuality', 'code-quality'],
    'operations' => ['Operations', 'operations'],
    'health' => ['Health', 'health'],
    'failed-job-monitor' => ['FailedJobMonitor', 'failed-job-monitor'],
    'backups' => ['Backups', 'backups'],
    'filament' => ['Filament', 'filament'],
]);

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

it('has ddd publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/ddd';

    expect(file_exists($basePath.'/Domain/DomainServiceProvider.php'))
        ->toBeTrue();
});

it('ddd command publishes expected files', function () {
    expect(commandSource('Ddd'))
        ->toContain("'Domain/DomainServiceProvider.php' => 'app/Domain/DomainServiceProvider.php'");
});

it('has ai publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/ai';

    expect(is_dir($basePath.'/.ai'))->toBeTrue()
        ->and(is_dir($basePath.'/.claude'))->toBeTrue();
});

it('has filament publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/filament';

    expect(is_dir($basePath.'/Filament'))
        ->toBeTrue();
});

it('has health publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/health';

    expect(file_exists($basePath.'/config/health.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Support/Notifiable.php'))->toBeTrue()
        ->and(file_exists($basePath.'/Support/Notification.php'))->toBeTrue();
});

it('has backups publishable files', function () {
    $basePath = dirname(__DIR__).'/publish/backups';

    expect(file_exists($basePath.'/config/backup.php'))
        ->toBeTrue();
});

it('every command class declares a publishes array', function (string $name) {
    expect(commandSource($name))
        ->toContain('public array $publishes = [');
})->with([
    'Ai',
    'Base',
    'Ddd',
    'CodeQuality',
    'Operations',
    'Health',
    'FailedJobMonitor',
    'Backups',
    'Filament',
]);

it('has health command class available', function () {
    expect(Health::class)->toBeString()
        ->and(FailedJobMonitor::class)->toBeString();
});

it('health config has result stores configured', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/config/health.php');

    expect($content)
        ->toContain('result_stores')
        ->toContain('JsonFileHealthResultStore::class');
});

it('health config has notifications configured', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/config/health.php');

    expect($content)
        ->toContain('notifications')
        ->toContain("'enabled' => true")
        ->toContain('use App\Support\Health\Notifiable;')
        ->toContain('Notifiable::class');
});

it('health config has discord webhook url setting', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/config/health.php');

    expect($content)
        ->toContain("'discord'")
        ->toContain("'webhook_url'")
        ->toContain('HEALTH_DISCORD_WEBHOOK_URL');
});

it('health notification file has to discord method', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/Support/Notification.php');

    expect($content)
        ->toContain('public function toDiscord()')
        ->toContain('DiscordMessage');
});

it('health notifiable file has route notification for discord method', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/health/Support/Notifiable.php');

    expect($content)
        ->toContain('public function routeNotificationForDiscord()')
        ->toContain("config('health.notifications.discord.webhook_url')");
});

it('backup config has backup source settings', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'backup'")
        ->toContain("'name'")
        ->toContain("'source'")
        ->toContain("'destination'");
});

it('backup config has notifications configured for discord', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'notifications'")
        ->toContain("'discord'")
        ->toContain("'webhook_url'")
        ->toContain('BACKUP_DISCORD_WEBHOOK_URL');
});

it('backup config has monitor backups settings', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'monitor_backups'")
        ->toContain("'health_checks'");
});

it('backup config has cleanup strategy configured', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'cleanup'")
        ->toContain("'strategy'")
        ->toContain("'default_strategy'");
});

it('backup config uses backups disk for destination', function () {
    $content = file_get_contents(dirname(__DIR__).'/publish/backups/config/backup.php');

    expect($content)
        ->toContain("'disks'")
        ->toContain("'backups'");
});

it('registers composer as singleton', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('register');

    $reflection = new ReflectionMethod(CookServiceProvider::class, 'register');

    expect($reflection->isPublic())
        ->toBeTrue();
});

it('registers all commands in boot method', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'boot');

    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain('Install::class')
        ->toContain('Base::class')
        ->toContain('Ai::class')
        ->toContain('CodeQuality::class')
        ->toContain('Operations::class')
        ->toContain('Health::class')
        ->toContain('FailedJobMonitor::class')
        ->toContain('Backups::class')
        ->toContain('Filament::class')
        ->toContain('Ui::class')
        ->toContain('Packages::class');
});

it('has files helper method', function () {
    expect(CookServiceProvider::class)
        ->toHaveMethod('files');
});

it('boot publishes registers commands with cook-prefixed tag', function () {
    $content = file_get_contents((new ReflectionClass(CookServiceProvider::class))->getFileName());

    expect($content)
        ->toContain('cook-{$command->publishGroup}');
});

it('base command publishes expected files', function () {
    expect(commandSource('Base'))
        ->toContain("'stubs' => 'stubs'")
        ->toContain("'Models/Model.php' => 'app/Models/Model.php'")
        ->toContain("'Models/Pivot.php' => 'app/Models/Pivot.php'");
});

it('ai command publishes expected files', function () {
    expect(commandSource('Ai'))
        ->toContain("'.ai' => '.ai'")
        ->toContain("'.claude' => '.claude'");
});

it('code quality command publishes expected files', function () {
    expect(commandSource('CodeQuality'))
        ->toContain("'.github' => '.github'")
        ->toContain("'phpstan.neon' => 'phpstan.neon'")
        ->toContain("'pint.json' => 'pint.json'")
        ->toContain("'rector.php' => 'rector.php'");
});

it('operations command publishes expected files', function () {
    expect(commandSource('Operations'))
        ->toContain("'config/one-time-operations.php' => 'config/one-time-operations.php'")
        ->toContain("'stubs/one-time-operation.stub' => 'stubs/one-time-operation.stub'");
});

it('health command publishes expected files', function () {
    expect(commandSource('Health'))
        ->toContain("'config/health.php' => 'config/health.php'")
        ->toContain("'Support/Notifiable.php' => 'app/Support/Health/Notifiable.php'")
        ->toContain("'Support/Notification.php' => 'app/Support/Health/Notification.php'");
});

it('failed job monitor command publishes expected files', function () {
    expect(commandSource('FailedJobMonitor'))
        ->toContain("'config/failed-job-monitor.php' => 'config/failed-job-monitor.php'")
        ->toContain("'Support/Notifiable.php' => 'app/Support/FailedJobMonitor/Notifiable.php'")
        ->toContain("'Support/Notification.php' => 'app/Support/FailedJobMonitor/Notification.php'");
});

it('backups command publishes expected files', function () {
    expect(commandSource('Backups'))
        ->toContain("'config/backup.php' => 'config/backup.php'");
});

it('filament command publishes expected files', function () {
    expect(commandSource('Filament'))
        ->toContain("'Filament' => 'app/Filament'");
});

it('files helper method is protected', function () {
    $reflection = new ReflectionMethod(CookServiceProvider::class, 'files');

    expect($reflection->isProtected())
        ->toBeTrue();
});
