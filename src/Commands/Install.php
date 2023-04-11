<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command;

class Install extends Command
{
    protected $signature = 'cook:install {--force}';

    protected $description = 'Install';

    public function handle()
    {
        $this->call('cook:publish', ['--force' => $this->option('force')]);
    }
}
