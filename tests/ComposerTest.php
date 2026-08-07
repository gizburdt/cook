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

it('writes script directly to composer.json', function () {
    createComposerJson($this->composerJsonPath, [
        'scripts' => [
            'post-autoload-dump' => [
                '@php artisan package:discover',
            ],
        ],
    ]);

    $composer = createComposer($this->tempDir);

    $result = $composer->addScript('post-autoload-dump', '@php artisan filament:upgrade');

    expect($result)->toBe(0);

    $content = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($content['scripts']['post-autoload-dump'])
        ->toBe([
            '@php artisan package:discover',
            '@php artisan filament:upgrade',
        ]);
});

it('builds correct command for require packages', function () {
    createComposerJson($this->composerJsonPath, []);

    $capturedCommand = null;

    $composer = createComposerWithProcessCapture($this->tempDir, $capturedCommand);

    $composer->requirePackages(['laravel/sanctum', 'spatie/laravel-permission']);

    expect($capturedCommand)
        ->not->toBeNull()
        ->toContain('require')
        ->toContain('laravel/sanctum')
        ->toContain('spatie/laravel-permission')
        ->not->toContain('--dev');
});

it('builds correct command for require packages as dev', function () {
    createComposerJson($this->composerJsonPath, []);

    $capturedCommand = null;

    $composer = createComposerWithProcessCapture($this->tempDir, $capturedCommand);

    $composer->requirePackages(['laravel/sanctum'], dev: true);

    expect($capturedCommand)
        ->not->toBeNull()
        ->toContain('require')
        ->toContain('laravel/sanctum')
        ->toContain('--dev');
});

it('returns empty required packages when composer.json does not exist', function () {
    $composer = createComposer($this->tempDir);

    expect($composer->getRequiredPackages())->toBeEmpty();
});

it('maps required packages to the section they are required in', function () {
    createComposerJson($this->composerJsonPath, [
        'require' => [
            'laravel/framework' => '^12.0',
        ],
        'require-dev' => [
            'phpunit/phpunit' => '^12.0',
        ],
    ]);

    $composer = createComposer($this->tempDir);

    expect($composer->getRequiredPackages()->all())
        ->toBe([
            'phpunit/phpunit' => 'dev',
            'laravel/framework' => 'require',
        ]);
});

it('handles a missing require section in composer.json', function () {
    createComposerJson($this->composerJsonPath, [
        'name' => 'test/package',
    ]);

    $composer = createComposer($this->tempDir);

    expect($composer->getRequiredPackages())->toBeEmpty();
});

it('actually writes script to composer.json when no scripts exist', function () {
    createComposerJson($this->composerJsonPath, [
        'name' => 'test/package',
    ]);

    $composer = createComposer($this->tempDir);

    $composer->addScript('post-autoload-dump', '@php artisan package:discover');

    $content = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($content['scripts']['post-autoload-dump'])
        ->toBe(['@php artisan package:discover']);
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

it('adds autoload file to composer.json', function () {
    createComposerJson($this->composerJsonPath, [
        'name' => 'test/package',
    ]);

    $composer = createComposer($this->tempDir);

    $composer->addAutoloadFile('app/helpers.php');

    $content = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($content['autoload']['files'])
        ->toBe(['app/helpers.php']);
});

it('does not duplicate autoload file in composer.json', function () {
    createComposerJson($this->composerJsonPath, [
        'autoload' => [
            'files' => [
                'app/helpers.php',
            ],
        ],
    ]);

    $composer = createComposer($this->tempDir);

    $composer->addAutoloadFile('app/helpers.php');

    $content = json_decode(file_get_contents($this->composerJsonPath), true);

    expect($content['autoload']['files'])
        ->toBe(['app/helpers.php']);
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
