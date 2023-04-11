<?php

namespace Gizburdt\Cook\Commands;

abstract class GenerateCommand extends Command
{
    protected $stub;

    protected $path;

    public function handle()
    {
        $fullPath = $this->laravel->basePath("{$this->path()}");

        if ($this->files->exists($fullPath) && ! $this->option('force')) {
            $this->error("{$this->file()} already exists!");

            return Command::FAILURE;
        }

        $this->info("Generating {$this->file()}...");

        $this->createParentDirectory($this->folder());

        $this->files->put($fullPath, $this->contents());

        $this->info('Created!');

        $this->after();

        return Command::SUCCESS;
    }

    protected function contents(): string
    {
        return $this->stubContents();
    }

    protected function stubContents(): string
    {
        return $this->files->get(__DIR__."/../../stubs/{$this->stub()}");
    }

    protected function path(): string
    {
        return $this->path;
    }

    protected function file(): string
    {
        return basename($this->path);
    }

    protected function folder(): string
    {
        return dirname($this->path);
    }

    protected function stub(): string|null
    {
        return $this->stub;
    }

    protected function after()
    {
        //
    }
}
