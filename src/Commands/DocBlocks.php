<?php

namespace Gizburdt\Cook\Commands;

use function Laravel\Prompts\text;

class DocBlocks extends Command
{
    protected $signature = 'burn:doc-blocks {path?}';

    protected $description = 'Remove all multiline comments';

    protected $files;

    public function handle()
    {
        $path = $this->argument('path') ?? text(
            label: 'Path',
            placeholder: 'app/Models',
            default: 'app/Models',
            required: true,
        );

        $files = $this->files->glob($this->laravel->basePath("{$path}/*.php"));

        if (! count($files)) {
            $this->error("No files found in {$path}");

            return Command::FAILURE;
        }

        $this->info('Removing doc blocks...');

        $this->withProgressBar($files, function ($file) {
            $this->files->put($file, $this->contents($file));
        });

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
