<?php

namespace Gizburdt\Cook\Commands;

class Publish extends Command
{
    protected $signature = 'cook:publish {--force}';

    protected $description = 'Publish all files';

    public function handle()
    {
        $this->call('vendor:publish', [
            '--provider' => 'Gizburdt\Cook\CookServiceProvider',
            '--force' => $this->option('force'),
        ]);
    }
}
