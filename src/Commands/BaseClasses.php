<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\RemoveEloquentModel;
use Illuminate\Support\Str;

class BaseClasses extends Command
{
    use UsesPhpParser;

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

        $files = collect($files)->reject(function ($file) {
            return Str::of($file)->contains('Model.php');
        })->toArray();

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
