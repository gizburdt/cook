<?php

namespace Gizburdt\Cook\Commands;

class Model extends Command
{
    protected $signature = 'cook:model';

    protected $description = 'Publish Model and stuff';

    public function handle()
    {
        $files = $this->files->glob($this->laravel->basePath('app/Models/*.php'));

        $this->info('Removing Illuminate\Database\Eloquent\Model...');

        $this->withProgressBar($files, function ($file) {
            $this->files->put($file, $this->contents($file));
        });
    }

    protected function contents($file)
    {
        $contents = $this->files->get($file);

        $contents = str_replace("use Illuminate\\Database\\Eloquent\\Model;\n", '', $contents);

        return $contents;
    }
}
