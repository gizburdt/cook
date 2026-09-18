<?php

use Gizburdt\Cook\Commands\Concerns\InstallsPassport;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;

if (class_exists(Application::class)) {
    beforeEach(fn () => Container::setInstance(new Application('/base')));

    afterEach(fn () => Container::setInstance(null));
} else {
    function app_path(string $path = ''): string
    {
        return "/base/app/{$path}";
    }

    function config_path(string $path = ''): string
    {
        return "/base/config/{$path}";
    }

    function database_path(string $path = ''): string
    {
        return "/base/database/{$path}";
    }
}

function makePassportInstaller(bool $installed): object
{
    return new class($installed)
    {
        use InstallsPassport;

        public array $calls = [];

        public object $components;

        public function __construct(public bool $installed)
        {
            $this->components = new class
            {
                public function info(string $message): void
                {
                    //
                }
            };
        }

        public function run(): void
        {
            $this->installPassport();
        }

        protected function hasInstallablePackages(array $packages): bool
        {
            return ! $this->installed;
        }

        protected function installPackages(array $packages): void
        {
            $this->calls['installPackages'] = $packages;
        }

        protected function runInNewProcess($command): bool
        {
            $this->calls['runInNewProcess'][] = $command;

            return true;
        }

        protected function applyPhpVisitors(string $file, array $visitors): void
        {
            $this->calls['applyPhpVisitors'][] = $file;
        }
    };
}

it('skips entirely when passport is already installed', function () {
    $installer = makePassportInstaller(installed: true);

    $installer->run();

    expect($installer->calls)->toBe([]);
});

it('installs passport when it is not yet installed', function () {
    $installer = makePassportInstaller(installed: false);

    $installer->run();

    expect($installer->calls['installPackages'])
        ->toBe(['laravel/passport' => 'require'])
        ->and($installer->calls['runInNewProcess'])
        ->toContain('php artisan install:api --passport --no-interaction')
        ->and($installer->calls['applyPhpVisitors'])
        ->toBe([
            app_path('Models/User.php'),
            config_path('auth.php'),
            database_path('seeders/DatabaseSeeder.php'),
        ]);
});
