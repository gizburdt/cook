<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\ReplaceStubs;

class AuthJson extends GenerateCommand
{
    use ReplaceStubs;

    protected $signature = 'cook:auth-json {username} {password} {--force}';

    protected $description = 'Create an auth.json';

    protected $subject = 'auth.json';

    protected $file = 'auth.json';

    protected $folder = '/';

    protected $stub = 'auth-json';

    public function contents(): string
    {
        return $this->replaceStubs([
            '{{ username }}' => $this->argument('username'),
            '{{ password }}' => $this->argument('password'),
        ], $this->stubContents());
    }
}
