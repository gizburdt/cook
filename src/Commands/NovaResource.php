<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class NovaResource extends Command
{
    protected $signature = 'cook:base-nova-resource {--force}';

    protected $description = 'Create a base Nova resource';

    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        parent::__construct();
    }

    public function handle()
    {
        $this->info('Creating Nova Resource');

        $filePath = 'app/Nova/Resource.php';

        if ($this->files->exists($filePath) && ! $this->option('force')) {
            $this->error('Resource.php already exists!');

            return Command::FAILURE;
        }

        if (! $this->files->exists($filePath)) {
            $this->files->makeDirectory($this->laravel->basePath('app/Nova'));
        }

        $fullPath = $this->laravel->basePath($filePath);

        $contents = $this->files->get(__DIR__.'/../../stubs/nova-resource.stub');

        $this->files->put($fullPath, $contents);

        $this->info('Created!');

        return Command::SUCCESS;
    }
}
