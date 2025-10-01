<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\NodeVisitors\RemoveEloquentModel;

class Model extends Command
{
    protected $signature = 'cook:model';

    protected $description = 'Publish Model and stuff';

    public function handle(): void
    {
        $files = $this->files->glob(
            $this->laravel->basePath('app/Models/*.php')
        );

        $this->info('Removing Eloquent\Model');

        $this->withProgressBar($files, function ($file) {
            $content = $this->files->get($file);

            $content = $this->parseContent($content, [
                RemoveEloquentModel::class,
            ]);

            $this->files->put($file, $content);
        });
    }
}
