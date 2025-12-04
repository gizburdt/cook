<?php

use Gizburdt\Cook\Composer;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/cook-test-'.uniqid();

    mkdir($this->tempDir);

    $this->composerJsonPath = $this->tempDir.'/composer.json';
});

afterEach(function () {
    if (file_exists($this->composerJsonPath)) {
        unlink($this->composerJsonPath);
    }

    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

it('returns empty array when no scripts exist', function () {
    createComposerJson($this->composerJsonPath, []);

    $composer = createComposer($this->tempDir);

    expect($composer->getScripts('post-autoload-dump'))
        ->toBe([]);
});

it('returns scripts as array when single script exists', function () {
    createComposerJson($this->composerJsonPath, [
        'scripts' => [
            'post-autoload-dump' => '@php artisan package:discover',
        ],
    ]);

    $composer = createComposer($this->tempDir);

    expect($composer->getScripts('post-autoload-dump'))
        ->toBe(['@php artisan package:discover']);
});

it('returns scripts array when multiple scripts exist', function () {
    createComposerJson($this->composerJsonPath, [
        'scripts' => [
            'post-autoload-dump' => [
                '@php artisan package:discover',
                '@php artisan filament:upgrade',
            ],
        ],
    ]);

    $composer = createComposer($this->tempDir);

    expect($composer->getScripts('post-autoload-dump'))
        ->toBe([
            '@php artisan package:discover',
            '@php artisan filament:upgrade',
        ]);
});

it('returns zero when script already exists', function () {
    createComposerJson($this->composerJsonPath, [
        'scripts' => [
            'post-autoload-dump' => [
                '@php artisan package:discover',
            ],
        ],
    ]);

    $composer = createComposer($this->tempDir);

    expect($composer->addScript('post-autoload-dump', '@php artisan package:discover'))
        ->toBe(0);
});

it('builds correct command for adding script', function () {
    createComposerJson($this->composerJsonPath, [
        'scripts' => [
            'post-autoload-dump' => [
                '@php artisan package:discover',
            ],
        ],
    ]);

    $capturedCommand = null;

    $composer = createComposerWithProcessCapture($this->tempDir, $capturedCommand);

    $composer->addScript('post-autoload-dump', '@php artisan filament:upgrade');

    expect($capturedCommand)
        ->not->toBeNull()
        ->toContain('config')
        ->toContain('scripts.post-autoload-dump')
        ->toContain('@php artisan package:discover')
        ->toContain('@php artisan filament:upgrade');
});

it('builds correct command for install packages', function () {
    createComposerJson($this->composerJsonPath, []);

    $capturedCommand = null;

    $composer = createComposerWithProcessCapture($this->tempDir, $capturedCommand);

    $composer->installPackages(['laravel/sanctum', 'spatie/laravel-permission']);

    expect($capturedCommand)
        ->not->toBeNull()
        ->toContain('require')
        ->toContain('laravel/sanctum')
        ->toContain('spatie/laravel-permission');
});

it('builds correct command for install packages with extra', function () {
    createComposerJson($this->composerJsonPath, []);

    $capturedCommand = null;

    $composer = createComposerWithProcessCapture($this->tempDir, $capturedCommand);

    $composer->installPackages(['laravel/sanctum'], '--dev');

    expect($capturedCommand)
        ->not->toBeNull()
        ->toContain('require')
        ->toContain('laravel/sanctum')
        ->toContain('--dev');
});

it('actually writes script to composer.json', function () {
    createComposerJson($this->composerJsonPath, [
        'scripts' => [
            'post-autoload-dump' => [
                '@php artisan package:discover',
            ],
        ],
    ]);

    $composer = createComposer($this->tempDir);

    $composer->addScript('post-autoload-dump', '@php artisan filament:upgrade');

    $content = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($content['scripts']['post-autoload-dump'])
        ->toBe([
            '@php artisan package:discover',
            '@php artisan filament:upgrade',
        ]);
});

it('actually writes script to composer.json when no scripts exist', function () {
    createComposerJson($this->composerJsonPath, [
        'name' => 'test/package',
    ]);

    $composer = createComposer($this->tempDir);

    $composer->addScript('post-autoload-dump', '@php artisan package:discover');

    $content = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($content['scripts']['post-autoload-dump'])
        ->toBe('@php artisan package:discover');
});

it('does not duplicate script in composer.json', function () {
    createComposerJson($this->composerJsonPath, [
        'scripts' => [
            'post-autoload-dump' => [
                '@php artisan package:discover',
            ],
        ],
    ]);

    $composer = createComposer($this->tempDir);

    $composer->addScript('post-autoload-dump', '@php artisan package:discover');

    $content = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($content['scripts']['post-autoload-dump'])
        ->toBe(['@php artisan package:discover']);
});

function createComposerJson(string $path, array $content): void
{
    file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT));
}

function createComposer(string $workingPath): Composer
{
    return new Composer(new Filesystem, $workingPath);
}

function createComposerWithProcessCapture(string $workingPath, ?array &$capturedCommand): Composer
{
    return new class(new Filesystem, $workingPath, $capturedCommand) extends Composer
    {
        public function __construct(
            Filesystem $files,
            ?string $workingPath,
            private ?array &$capturedCommand
        ) {
            parent::__construct($files, $workingPath);
        }

        protected function getProcess(array $command, array $env = []): Process
        {
            $this->capturedCommand = $command;

            return new class extends Process
            {
                public function __construct() {}

                public function run(?callable $callback = null, array $env = []): int
                {
                    return 0;
                }

                public function setTimeout(?float $timeout): static
                {
                    return $this;
                }
            };
        }
    };
}
