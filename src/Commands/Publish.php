<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\CookServiceProvider;

class Publish extends Command
{
    protected $signature = 'cook:publish {--force}';

    protected $description = 'Publish all files';

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--provider' => CookServiceProvider::class,
            '--force' => $this->option('force'),
        ]);
    }
}
