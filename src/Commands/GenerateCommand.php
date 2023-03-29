<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

abstract class GenerateCommand extends Command
{
    protected $files;

    protected $subject;

    protected $file;

    protected $folder;

    protected $stub;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        parent::__construct();
    }

    public function handle()
    {
        $this->info("Creating {$this->subject}...");

        $fullPath = $this->laravel->basePath("{$this->folder}/{$this->file}");

        if ($this->files->exists($fullPath) && ! $this->option('force')) {
            $this->error("{$this->file} already exists!");

            return Command::FAILURE;
        }

        if ($this->folder && ! $this->files->exists($this->folder)) {
            $this->files->makeDirectory(
                $this->laravel->basePath($this->folder)
            );
        }

        $contents = $this->contents();

        $this->files->put($fullPath, $contents);

        $this->info('Created!');

        return Command::SUCCESS;
    }

    protected function contents(): string
    {
        return $this->stubContents();
    }

    protected function stubContents(): string
    {
        return $this->files->get(__DIR__."/../../stubs/{$this->stub}.stub");
    }
}
