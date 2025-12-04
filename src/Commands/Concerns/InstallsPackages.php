<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Illuminate\Support\Collection;
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
            $packages = $packages->keys()->reject(function ($package) use ($installed) {
                return $installed->contains($package);
            })->values();

            if ($packages->isEmpty()) {
                return;
            }

            $this->components->bulletList($packages->all());

            $extra = ($type === 'dev') ? '--dev' : '';

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
