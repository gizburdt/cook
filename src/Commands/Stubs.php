<?php

namespace Gizburdt\Cook\Commands;

class Stubs extends Command
{
    protected $signature = 'cook:stubs {--force}';

    protected $description = 'Install stubs';

    public function handle(): void
    {
        $this->components->info('Publishing files');

        $this->call('vendor:publish', [
            '--tag' => 'cook-stubs',
            '--force' => $this->option('force'),
        ]);
    }
}
