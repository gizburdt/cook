<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Illuminate\Support\Collection;

trait InstallsPackages
{
    protected function tryInstallPackages(): void
    {
        if (! $this->hasInstallablePackages($this->packages)) {
            return;
        }

        $this->components->info('Installing packages');

        $this->installPackages($this->packages);
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

    protected function hasInstallablePackages(array $packages): bool
    {
        $installed = $this->getInstalledPackages();

        return collect($packages)->keys()
            ->reject(fn ($package) => $installed->contains($package))
            ->isNotEmpty();
    }

    protected function getInstalledPackages(): Collection
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
