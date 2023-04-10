<?php

namespace Gizburdt\Cook\Commands;

abstract class PublishCommand extends Command
{
    protected $folder = __DIR__."/../../publish/";

    protected $publish = [];

    public function handle()
    {
        foreach ($this->publish() as $from => $to) {
            $file = basename($from);

            $from = $this->folder($from);

            $fullPath = "{$this->laravel->basePath()}/{$to}/{$file}";

            if ($this->files->exists($fullPath) && ! $this->option('force')) {
                $this->error("{$this->file()} already exists!");

                return Command::FAILURE;
            }

            if (! $this->files->exists($to)) {
                $this->files->makeDirectory($to);
            }

            $this->files->copy($from, $fullPath);

            $this->info("Published {$file} to {$to}");
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

    protected function publish(): array
    {
        return $this->publish;
    }

    protected function after()
    {
        return;
    }
}
