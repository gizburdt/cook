<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Laravel\Roster\Roster;

trait InstallsPackages
{
    protected function installPackages(array $packages): void
    {
        $installed = $this->getInstalledPackages();

        $packages = collect($packages)->groupBy(function ($type) {
            return $type;
        }, preserveKeys: true);

        $packages->each(function ($packages, $type) use ($installed) {
            $packages = $packages->keys()->reject(function ($package) use ($installed, $type) {
                return in_array($package, $installed);
            })->values()
;

            if ($packages->isEmpty()) {
                return;
            }

            $this->components->bulletList($packages->all());

            $extra = ($type === 'dev') ? '--dev' : '';

            $this->composer->installPackages($packages->all(), $extra);
        });
    }

    protected function getInstalledPackages(): array
    {
        $roster = Roster::scan();

        $rosterPackages = $roster->packages()->map(function ($package) {
            return $package->rawName();
        })->toArray();

        $composerLockPackages = $this->getComposerLockPackages();

        return array_unique(array_merge($rosterPackages, $composerLockPackages));
    }

    protected function getComposerLockPackages(): array
    {
        $lockFile = base_path('composer.lock');

        if (! file_exists($lockFile)) {
            return [];
        }

        $lock = json_decode(file_get_contents($lockFile), true);

        $packages = collect($lock['packages'] ?? [])
            ->pluck('name')
            ->toArray();

        $devPackages = collect($lock['packages-dev'] ?? [])
            ->pluck('name')
            ->toArray();

        return array_merge($packages, $devPackages);
    }
}
