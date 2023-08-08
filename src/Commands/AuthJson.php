<?php

namespace Gizburdt\Cook\Commands;

use function Laravel\Prompts\text;
use function Laravel\Prompts\password;
use Gizburdt\Cook\ReplacesContent;

class AuthJson extends GenerateCommand
{
    use ReplacesContent;

    protected $signature = 'cook:auth-json {username?} {password?} {--force}';

    protected $description = 'Create an auth.json';

    protected $stub = 'auth-json.stub';

    protected $path = '/auth.json';

    public function contents(): string
    {
        $username = $this->argument('username') ?? text(
            label: 'Username',
            placeholder: 'username',
            required: true,
        );

        $password = $this->argument('password') ?? password(
            label: 'Password',
            placeholder: 'password',
            required: true,
        );

        return $this->replaceContent([
            '{{ username }}' => $username,
            '{{ password }}' => $password,
        ], $this->stubContents());
    }
}
