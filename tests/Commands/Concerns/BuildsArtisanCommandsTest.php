<?php

use Gizburdt\Cook\Commands\Concerns\BuildsArtisanCommands;

it('builds artisan command without arguments', function () {
    $helper = createBuildsArtisanCommandsHelper();

    expect($helper->testBuildArtisanCommand('make:model'))
        ->toBe('php artisan make:model');
});

it('builds artisan command with positional arguments', function () {
    $helper = createBuildsArtisanCommandsHelper();

    expect($helper->testBuildArtisanCommand('make:model', ['User']))
        ->toBe('php artisan make:model User');
});

it('builds artisan command with named options', function () {
    $helper = createBuildsArtisanCommandsHelper();

    expect($helper->testBuildArtisanCommand('make:model', ['migration' => true]))
        ->toBe('php artisan make:model --migration=1');
});

it('builds artisan command with multiple named options', function () {
    $helper = createBuildsArtisanCommandsHelper();

    expect($helper->testBuildArtisanCommand('make:model', ['migration' => true, 'factory' => true]))
        ->toBe('php artisan make:model --migration=1 --factory=1');
});

it('builds artisan command with positional and named arguments', function () {
    $helper = createBuildsArtisanCommandsHelper();

    expect($helper->testBuildArtisanCommand('make:model', ['User', 'migration' => true]))
        ->toBe('php artisan make:model User --migration=1');
});

it('builds artisan command with string option value', function () {
    $helper = createBuildsArtisanCommandsHelper();

    expect($helper->testBuildArtisanCommand('make:model', ['User', 'path' => 'Models/Admin']))
        ->toBe('php artisan make:model User --path=Models/Admin');
});

it('builds artisan command with multiple positional arguments', function () {
    $helper = createBuildsArtisanCommandsHelper();

    expect($helper->testBuildArtisanCommand('make:migration', ['create_users_table', 'create' => 'users']))
        ->toBe('php artisan make:migration create_users_table --create=users');
});

function createBuildsArtisanCommandsHelper(): object
{
    return new class
    {
        use BuildsArtisanCommands;

        public function testBuildArtisanCommand(string $command, array $arguments = []): string
        {
            return $this->buildArtisanCommand($command, $arguments);
        }
    };
}
