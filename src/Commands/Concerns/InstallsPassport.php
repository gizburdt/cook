<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Gizburdt\Cook\Commands\NodeVisitors\AddApiGuard;
use Gizburdt\Cook\Commands\NodeVisitors\AddPassportHasApiTokens;
use Gizburdt\Cook\Commands\NodeVisitors\AddPassportPersonalAccessClient;

trait InstallsPassport
{
    protected function installPassport(): void
    {
        if ($this->getInstalledPackages()->contains('laravel/passport')) {
            return;
        }

        $this->components->info('Installing Passport');

        $this->installPackages(['laravel/passport' => 'require']);

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
