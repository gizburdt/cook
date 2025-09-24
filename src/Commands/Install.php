<?php

namespace Gizburdt\Cook\Commands;

use function Laravel\Prompts\confirm;

class Install extends Command
{
    protected $signature = 'cook:install {--force}';

    protected $description = 'Install';

    public function handle(): void
    {
        // Publish
        if (confirm(label: 'Run cook:publish?', default: true)) {
            $this->call('cook:publish', ['--force' => $this->option('force')]);
        }

        // Packages
        if (confirm(label: 'Run cook:packages?', default: true)) {
            $this->call('cook:packages');
        }

        // Models
        $this->call('cook:model');
    }
}
