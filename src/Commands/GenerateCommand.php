<?php

namespace Gizburdt\Cook\Commands;

abstract class GenerateCommand extends Command
{
    protected $subject;

    protected $file;

    protected $folder;

    protected $stub;

    public function handle()
    {
        $fullPath = $this->laravel->basePath("{$this->folder()}/{$this->file()}");

        if ($this->files->exists($fullPath) && ! $this->option('force')) {
            $this->error("{$this->file()} already exists!");

            return Command::FAILURE;
        }

        $this->info("Creating {$this->subject()}...");

        if ($this->folder() && ! $this->files->exists($this->folder())) {
            $this->files->makeDirectory(
                $this->laravel->basePath($this->folder())
            );
        }

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
        return $this->files->get(__DIR__."/../../stubs/{$this->stub()}.stub");
    }

    protected function subject(): string
    {
        return $this->subject;
    }

    protected function file(): string
    {
        return $this->file;
    }

    protected function folder(): string
    {
        return $this->folder;
    }

    protected function stub(): string|null
    {
        return $this->stub;
    }

    protected function after()
    {
        return;
    }
}
