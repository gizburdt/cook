<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\NodeVisitors\RemoveEloquentModel;

class BaseClasses extends Command
{
    protected $signature = 'cook:base-classes {--force}';

    protected $description = 'Install Model, Policy, Resource';

    public function handle(): void
    {
        $this->components->info('Publishing files');

        $this->call('vendor:publish', [
            '--tag' => 'cook-base-classes',
            '--force' => $this->option('force'),
        ]);

        $this->components->info('Replacing Eloquent Model');

        $this->replaceEloquentModel();
    }

    protected function replaceEloquentModel(): void
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
