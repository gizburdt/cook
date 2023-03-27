<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class AuthJson extends Command
{
    protected $signature = 'cook:auth-json {username} {password}';

    protected $description = 'Command description';

    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        parent::__construct();
    }

    public function handle()
    {
        $this->info('Creating auth.json...');

        $fullPath = $this->laravel->basePath('auth.json');

        $stub = $this->files->get(__DIR__.'/../../stubs/auth-json.stub');

        $contents = $this->replaceStubs([
            '{{ username }}' => $this->argument('username'),
            '{{ password }}' => $this->argument('password'),
        ], $stub);

        $this->files->put($fullPath, $contents);

        $this->info('Created!');

        return Command::SUCCESS;
    }

    protected function replaceStubs($replace, $stub): string
    {
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }
}
