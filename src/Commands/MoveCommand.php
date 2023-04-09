<?php

namespace Gizburdt\Cook\Commands;

abstract class MoveCommand extends Command
{
    protected $folder = __DIR__."/../../stubs/Traits";

    protected $move = [];

    public function handle()
    {
        foreach ($this->move() as $from => $to) {
            $file = basename($from);

            $from = $this->folder($file);

            $fullPath = "{$this->laravel->basePath()}/{$to}/{$file}";

            if ($this->files->exists($fullPath) && ! $this->option('force')) {
                $this->error("{$this->file()} already exists!");

                return Command::FAILURE;
            }

            if (! $this->files->exists($to)) {
                $this->files->makeDirectory($to);
            }

            $this->files->copy($from, $fullPath);

            $this->info("Moved {$file} to {$to}");
        }

        $this->after();

        return Command::SUCCESS;
    }

    protected function folder($append = null): string
    {
        return collect([
            $this->folder,
            $append,
        ])->filter()->implode('/');
    }

    protected function move(): array
    {
        return $this->move;
    }

    protected function after()
    {
        return;
    }
}
