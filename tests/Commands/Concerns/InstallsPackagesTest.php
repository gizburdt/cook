<?php

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/cook-packages-test-'.uniqid();

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

it('returns true when packages need to be installed', function () {
    writeComposerJson($this->composerJsonPath, [
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([
        'spatie/laravel-permission' => 'require',
    ]))->toBeTrue();
});

it('returns false when all packages are already required', function () {
    writeComposerJson($this->composerJsonPath, [
        'require' => [
            'laravel/framework' => '^12.0',
            'spatie/laravel-permission' => '^6.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([
        'laravel/framework' => 'require',
        'spatie/laravel-permission' => 'require',
    ]))->toBeFalse();
});

it('returns true when some packages need to be installed', function () {
    writeComposerJson($this->composerJsonPath, [
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([
        'laravel/framework' => 'require',
        'spatie/laravel-permission' => 'require',
    ]))->toBeTrue();
});

it('installs a dev package again when it is needed in require', function () {
    writeComposerJson($this->composerJsonPath, [
        'require-dev' => [
            'laravel/pail' => '^1.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testGetInstallablePackages(['laravel/pail' => 'require'])->all())
        ->toBe(['laravel/pail' => 'require']);
});

it('leaves a required package alone when it is only needed in dev', function () {
    writeComposerJson($this->composerJsonPath, [
        'require' => [
            'laravel/pail' => '^1.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages(['laravel/pail' => 'dev']))
        ->toBeFalse();
});

it('returns false when a dev package is already required as dev', function () {
    writeComposerJson($this->composerJsonPath, [
        'require-dev' => [
            'laravel/pail' => '^1.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages(['laravel/pail' => 'dev']))
        ->toBeFalse();
});

it('returns false for empty packages array', function () {
    writeComposerJson($this->composerJsonPath, [
        'require' => [],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([]))
        ->toBeFalse();
});

it('returns the section a removable package is required in', function () {
    writeComposerJson($this->composerJsonPath, [
        'require' => [
            'laravel/framework' => '^12.0',
        ],
        'require-dev' => [
            'phpunit/phpunit' => '^12.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testGetRemovablePackages([
        'phpunit/phpunit' => 'dev',
        'laravel/framework' => 'dev',
    ])->all())->toBe([
        'phpunit/phpunit' => 'dev',
        'laravel/framework' => 'require',
    ]);
});

it('returns false when packages to remove are not required', function () {
    writeComposerJson($this->composerJsonPath, [
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasRemovablePackages(['phpunit/phpunit' => 'dev']))
        ->toBeFalse();
});

it('returns false for empty remove packages array', function () {
    writeComposerJson($this->composerJsonPath, [
        'require-dev' => [
            'phpunit/phpunit' => '^12.0',
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasRemovablePackages([]))
        ->toBeFalse();
});

function writeComposerJson(string $path, array $content): void
{
    file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT));
}

function createPackageInstaller(string $tempDir): object
{
    return new class(new Composer(new Filesystem, $tempDir))
    {
        use InstallsPackages;

        public function __construct(protected Composer $composer) {}

        public function testGetInstallablePackages(array $packages): Collection
        {
            return $this->getInstallablePackages($packages);
        }

        public function testHasInstallablePackages(array $packages): bool
        {
            return $this->hasInstallablePackages($packages);
        }

        public function testGetRemovablePackages(array $packages): Collection
        {
            return $this->getRemovablePackages($packages);
        }

        public function testHasRemovablePackages(array $packages): bool
        {
            return $this->hasRemovablePackages($packages);
        }
    };
}
