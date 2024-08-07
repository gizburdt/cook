<?php

namespace Gizburdt\Cook\Commands;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class Install extends Command
{
    protected $signature = 'cook:install {--force}';

    protected $description = 'Install';

    public function handle()
    {
        // Publish
        if (confirm(label: 'Run cook:publish?', default: true)) {
            $this->call('cook:publish', ['--force' => $this->option('force')]);
        }

        // Packages
        $this->call('cook:packages');

        // Models
        $this->call('cook:model');
    }
}
