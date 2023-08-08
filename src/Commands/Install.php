<?php

namespace Gizburdt\Cook\Commands;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\password;

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

        // auth.json
        if (confirm(label: 'Run cook:auth-json?', default: true)) {
            $this->authJson();
        }

        // Burn
        if (confirm(label: 'Run burn:doc-blocks?', default: true)) {
            $this->docBlocks();
        }
    }

    protected function authJson()
    {
        $username = text(
            label: 'Username?',
            placeholder: 'Username', required: true
        );

        $password = text(
            label: 'Password?',
            placeholder: 'Password',
            required: true
        );;

        $this->call('cook:auth-json', [
            'username' => $username,
            'password' => $password,
            '--force' => true,
        ]);
    }

    protected function docBlocks()
    {
        $path = text(
            label: 'Path',
            placeholder: 'app/Models',
            default: 'app/Models',
            required: true,
        );

        $this->call('burn:doc-blocks', [
            'path' => $path
        ]);
    }
}
