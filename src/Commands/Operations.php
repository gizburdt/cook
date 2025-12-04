<?php

namespace Gizburdt\Cook\Commands;

class Operations extends Command
{
    protected $signature = 'cook:operations {--force}';

    protected $description = 'Publish one-time-operations files';

    public function handle(): void
    {
        $this->components->info('Publishing files');

        $this->call('vendor:publish', [
            '--tag' => 'cook-operations',
            '--force' => $this->option('force'),
        ]);
    }
}
