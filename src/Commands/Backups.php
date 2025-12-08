<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsSchedule;

class Backups extends Command
{
    use InstallsPackages;
    use UsesEnvParser;
    use UsesPhpParser;

    protected $signature = 'cook:backups {--force}';

    protected $description = 'Install backups';

    protected array $packages = [
        'spatie/laravel-backup' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-backups',
            '--force' => $this->option('force'),
        ]);

        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }

        $this->addCode();
    }

    protected function addCode(): void
    {
        $this->components->info('Adding schedule');

        $this->addSchedule();

        $this->components->info('Adding environment variables');

        $this->addEnvVariables([
            'BACKUP_DISCORD_WEBHOOK_URL' => '',
        ]);
    }

    protected function addSchedule(): void
    {
        $file = base_path('routes/console.php');

        $content = $this->files->get($file);

        $content = $this->parseContent($content, [
            AddBackupsSchedule::class,
        ]);

        $this->files->put($file, $content);
    }
}
