<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Gizburdt\Cook\Commands\NodeVisitors\AddApiGuard;
use Gizburdt\Cook\Commands\NodeVisitors\AddPassportHasApiTokens;
use Gizburdt\Cook\Commands\NodeVisitors\AddPassportPersonalAccessClient;

trait InstallsPassport
{
    protected function installPassport(): void
    {
        $packages = ['laravel/passport' => 'require'];

        if (! $this->hasInstallablePackages($packages)) {
            return;
        }

        $this->components->info('Installing Passport');

        $this->installPackages($packages);

        $this->runInNewProcess('php artisan install:api --passport --no-interaction');

        $this->applyPhpVisitors(app_path('Models/User.php'), [
            AddPassportHasApiTokens::class,
        ]);

        $this->applyPhpVisitors(config_path('auth.php'), [
            AddApiGuard::class,
        ]);

        $this->applyPhpVisitors(database_path('seeders/DatabaseSeeder.php'), [
            AddPassportPersonalAccessClient::class,
        ]);
    }
}
