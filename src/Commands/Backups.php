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
        'awssat/discord-notification-channel' => 'require',
        'spatie/laravel-backup' => 'require',
    ];

    public function handle(): void
    {
        $this->driver = select('Which driver?', [
            'minio' => 'MinIO',
            'google' => 'Google',
            'local' => 'Local',
        ], 'minio');

        $this->setupDriver();

        $this->call('vendor:publish', [
            '--tag' => 'cook-backups',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->addCode();
    }

    protected function setupDriver(): void
    {
        if ($this->driver === 'google') {
            $this->packages['yaza/laravel-google-drive-storage'] = 'require';
        }

        if ($this->driver === 'minio') {
            $this->packages['league/flysystem-aws-s3-v3'] = 'require';
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
                'BACKUP_GOOGLE_CLIENT_ID' => '',
                'BACKUP_GOOGLE_CLIENT_SECRET' => '',
                'BACKUP_GOOGLE_REFRESH_TOKEN' => '',
                'BACKUP_GOOGLE_FOLDER' => '',
            ]);
        }

        if ($this->driver === 'minio') {
            $this->addEnvVariables([
                'BACKUP_S3_KEY' => '',
                'BACKUP_S3_SECRET' => '',
                'BACKUP_S3_BUCKET' => '',
                'BACKUP_S3_ENDPOINT' => '',
                'BACKUP_S3_REGION' => 'eu-central-1',
            ]);
        }

        $this->components->info('Adding config');

        $this->addConfig();
    }

    protected function addConfig(): void
    {
        $this->applyPhpVisitors(config_path('filesystems.php'), [
            new AddBackupsDisk($this->driver),
        ]);
    }

    protected function addSchedule(): void
    {
        $this->applyPhpVisitors(base_path('routes/console.php'), [
            AddBackupsSchedule::class,
        ]);
    }
}
