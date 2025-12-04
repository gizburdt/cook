<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Illuminate\Support\Collection;
use Laravel\Roster\Roster;

trait InstallsPackages
{
    protected function hasInstallablePackages(array $packages): bool
    {
        $installed = $this->getInstalledPackages();

        return collect($packages)->keys()
            ->reject(fn ($package) => $installed->contains($package))
            ->isNotEmpty();
    }

    protected function installPackages(array $packages): void
    {
        if (! $this->hasInstallablePackages($packages)) {
            return;
        }

        $installed = $this->getInstalledPackages();

        $packageGroups = collect($packages)->groupBy(function ($group) {
            return $group;
        }, preserveKeys: true);

        $packageGroups->each(function ($packages, $group) use ($installed) {
            if (! $this->hasInstallablePackages($packages->all())) {
                return;
            }

            $packages = $packages->keys()
                ->reject(fn ($package) => $installed->contains($package))
                ->values();

            $this->components->bulletList($packages->all());

            $extra = ($group === 'dev') ? '--dev' : '';

            $this->composer->installPackages($packages->all(), $extra);
        });
    }

    protected function getInstalledPackages(): Collection
    {
        return Roster::scan()
            ->packages()
            ->map(fn ($package) => $package->rawName())
            ->merge($this->getComposerLockPackages())
            ->unique()
            ->values();
    }

    protected function getComposerLockPackages(): Collection
    {
        $lockFile = base_path('composer.lock');

        if (! file_exists($lockFile)) {
            return collect();
        }

        $lock = json_decode(file_get_contents($lockFile), true);

        return collect($lock['packages'] ?? [])
            ->merge($lock['packages-dev'] ?? [])
            ->pluck('name');
    }
}
