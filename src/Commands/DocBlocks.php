<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class DocBlocks extends Command
{
    protected $signature = 'burn:doc-blocks {path}';

    protected $description = 'Remove all multiline comments';

    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        parent::__construct();
    }

    public function handle()
    {
        $path = $this->argument('path');

        $files = $this->files->glob($this->laravel->basePath("{$path}/*.php"));

        if (! count($files)) {
            $this->error("No files found in {$path}");

            return Command::FAILURE;
        }

        $this->info('Removing doc blocks...');

        $this->withProgressBar($files, function ($file) {
            $this->files->put($file, $this->contents($file));
        });

        $this->info('Done!');

        return Command::SUCCESS;
    }

    protected function contents($file): string
    {
        $contents = preg_replace(
            "!/\*.*?\*/!s", '', $this->files->get($file)
        );

        $contents = preg_replace(
            "/\n\s*\n\s*\n/", "\n\n", $contents
        );

        return $contents;
    }
}
