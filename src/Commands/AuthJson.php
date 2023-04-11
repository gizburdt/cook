<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\ReplacesContent;

class AuthJson extends GenerateCommand
{
    use ReplacesContent;

    protected $signature = 'cook:auth-json {username} {password} {--force}';

    protected $description = 'Create an auth.json';

    protected $stub = 'auth-json.stub';

    protected $path = '/auth.json';

    public function contents(): string
    {
        return $this->replaceContent([
            '{{ username }}' => $this->argument('username'),
            '{{ password }}' => $this->argument('password'),
        ], $this->stubContents());
    }
}
