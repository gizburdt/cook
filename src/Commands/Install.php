<?php

namespace Gizburdt\Cook\Commands;

class Install extends Command
{
    protected $signature = 'cook:install {--force}';

    protected $description = 'Install';

    public function handle()
    {
        // Publish
        if ($this->confirm('Run cook:publish?', false)) {
            $this->call('cook:publish', ['--force' => $this->option('force')]);
        }

        // Packages
        $this->call('cook:packages');

        // Models
        $this->call('cook:model');

        // auth.json
        if ($this->confirm('Run cook:auth-json?', true)) {
            $this->call('cook:auth-json', [
                'username' => $this->ask('Username?'),
                'password' => $this->ask('Password?'),
            ]);
        }

        // Burn
        if ($this->confirm('Run burn:doc-blocks?', true)) {
            $this->call('burn:doc-blocks', ['path' => $this->ask('Path?', 'app/Models')]);
        }
    }
}
