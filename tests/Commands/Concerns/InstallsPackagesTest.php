<?php

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/cook-packages-test-'.uniqid();

    mkdir($this->tempDir);

    $this->composerLockPath = $this->tempDir.'/composer.lock';
});

afterEach(function () {
    if (file_exists($this->composerLockPath)) {
        unlink($this->composerLockPath);
    }

    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

it('returns empty collection when composer.lock does not exist', function () {
    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testGetInstalledPackages())
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});

it('returns installed packages from composer.lock', function () {
    createComposerLock($this->composerLockPath, [
        'packages' => [
            ['name' => 'laravel/framework'],
            ['name' => 'spatie/laravel-permission'],
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testGetInstalledPackages())
        ->toContain('laravel/framework')
        ->toContain('spatie/laravel-permission');
});

it('returns both require and require-dev packages', function () {
    createComposerLock($this->composerLockPath, [
        'packages' => [
            ['name' => 'laravel/framework'],
        ],
        'packages-dev' => [
            ['name' => 'phpunit/phpunit'],
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testGetInstalledPackages())
        ->toContain('laravel/framework')
        ->toContain('phpunit/phpunit');
});

it('returns true when packages need to be installed', function () {
    createComposerLock($this->composerLockPath, [
        'packages' => [
            ['name' => 'laravel/framework'],
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([
        'spatie/laravel-permission' => 'require',
    ]))->toBeTrue();
});

it('returns false when all packages are already installed', function () {
    createComposerLock($this->composerLockPath, [
        'packages' => [
            ['name' => 'laravel/framework'],
            ['name' => 'spatie/laravel-permission'],
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([
        'laravel/framework' => 'require',
        'spatie/laravel-permission' => 'require',
    ]))->toBeFalse();
});

it('returns true when some packages need to be installed', function () {
    createComposerLock($this->composerLockPath, [
        'packages' => [
            ['name' => 'laravel/framework'],
        ],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([
        'laravel/framework' => 'require',
        'spatie/laravel-permission' => 'require',
    ]))->toBeTrue();
});

it('returns false for empty packages array', function () {
    createComposerLock($this->composerLockPath, [
        'packages' => [],
    ]);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testHasInstallablePackages([]))
        ->toBeFalse();
});

it('handles missing packages array in composer.lock', function () {
    createComposerLock($this->composerLockPath, []);

    $installer = createPackageInstaller($this->tempDir);

    expect($installer->testGetInstalledPackages())
        ->toBeEmpty();
});

function createComposerLock(string $path, array $content): void
{
    file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT));
}

function createPackageInstaller(string $tempDir): object
{
    return new class($tempDir)
    {
        use InstallsPackages {
            getInstalledPackages as traitGetInstalledPackages;
            hasInstallablePackages as traitHasInstallablePackages;
        }

        public function __construct(protected string $basePath) {}

        public function testGetInstalledPackages(): Collection
        {
            return $this->getInstalledPackages();
        }

        public function testHasInstallablePackages(array $packages): bool
        {
            return $this->hasInstallablePackages($packages);
        }

        protected function getInstalledPackages(): Collection
        {
            $lockFile = $this->basePath.'/composer.lock';

            if (! file_exists($lockFile)) {
                return collect();
            }

            $lock = json_decode(file_get_contents($lockFile), true);

            return collect($lock['packages'] ?? [])
                ->merge($lock['packages-dev'] ?? [])
                ->pluck('name');
        }
    };
}
