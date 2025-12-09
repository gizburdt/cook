<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsDisk;
use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsSchedule;

use function Laravel\Prompts\select;

class Backups extends Command
{
    use InstallsPackages;
    use UsesEnvParser;
    use UsesPhpParser;

    protected $signature = 'cook:backups {--force}';

    protected $description = 'Install backups';

    protected string $driver;

    protected array $packages = [
        'spatie/laravel-backup' => 'require',
    ];

    public function handle(): void
    {
        $this->driver = select('Which driver?', [
            'local' => 'Local',
            'google' => 'Google Drive',
        ], 'google');

        $this->setupDriver();

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

    protected function setupDriver(): void
    {
        if ($this->driver === 'google') {
            $this->packages['yaza/laravel-google-drive-storage'] = 'require';
        }
    }

    protected function addCode(): void
    {
        $this->components->info('Adding schedule');

        $this->addSchedule();

        $this->components->info('Adding environment variables');

        $this->addEnvVariables([
            'BACKUP_DISCORD_WEBHOOK_URL' => '',
        ]);

        if ($this->driver === 'google') {
            $this->addEnvVariables([
                'GOOGLE_DRIVE_CLIENT_ID' => '',
                'GOOGLE_DRIVE_CLIENT_SECRET' => '',
                'GOOGLE_DRIVE_REFRESH_TOKEN' => '',
                'GOOGLE_DRIVE_FOLDER' => '',
            ]);
        }

        $this->components->info('Adding config');

        $this->addConfig();
    }

    protected function addConfig(): void
    {
        $this->components->info('Adding backups disk to filesystems config');

        $file = config_path('filesystems.php');

        $content = $this->files->get($file);

        $content = $this->parseContent($content, [
            new AddBackupsDisk($this->driver),
        ]);

        $this->files->put($file, $content);
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
