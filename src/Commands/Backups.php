<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsDisk;
use Gizburdt\Cook\Composer;
use Illuminate\Filesystem\Filesystem;

class Backups extends Command
{
    protected $signature = 'cook:backups';

    protected $description = 'Setup backups';

    public function __construct(Filesystem $files, protected Composer $composer)
    {
        parent::__construct($files);
    }

    public function handle(): void
    {
        // $this->info('Installing package');
        //
        // $this->composer->installPackages(['spatie/laravel-backup']);
        //
        // $this->info('Publishing config');
        //
        // $this->files->copy(__DIR__.'/../../publish/config/backup.php', base_path('config/backup.php'));
        //
        // $this->info('Setup backups disk');

        $this->setupBackupsDisk();
    }

    protected function setupBackupsDisk(): void
    {
        $file = base_path('config/filesystems.php');

        $content = $this->files->get($file);

        $content = $this->parseContent($content, [
            AddBackupsDisk::class,
        ]);

        $this->files->put($file, $content);
    }
}
