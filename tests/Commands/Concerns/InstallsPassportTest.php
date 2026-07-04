<?php

use Gizburdt\Cook\Commands\Concerns\InstallsPassport;
use Illuminate\Support\Collection;

if (! function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return $path;
    }
}

if (! function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return $path;
    }
}

if (! function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return $path;
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

        protected function getInstalledPackages(): Collection
        {
            return collect($this->installed ? ['laravel/passport'] : []);
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
        ->toBe(['Models/User.php', 'auth.php', 'seeders/DatabaseSeeder.php']);
});
