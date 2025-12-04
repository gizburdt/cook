<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsDisk;
use Gizburdt\Cook\Composer;

class Backups extends Command
{
    protected $signature = 'cook:backups {--force}';

    protected $description = 'Install backups';

    protected array $packages = [
        'awssat/discord-notification-channel' => 'require',
        'spatie/laravel-backup' => 'require',
    ];

    public function handle(): void
    {
        //
    }
}
