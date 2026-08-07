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

    protected function tryRemovePackages(): void
    {
        if (! property_exists($this, 'removePackages') || empty($this->removePackages)) {
            return;
        }

        if (! $this->hasRemovablePackages($this->removePackages)) {
            return;
        }

        $this->components->info('Removing packages');

        $required = $this->getRequiredPackages();

        $packageGroups = collect($this->removePackages)
            ->filter(fn ($group, $package) => $required->has($package))
            ->groupBy(fn ($group, $package) => $required->get($package), preserveKeys: true);

        $packageGroups->each(function ($packages, $group) {
            $packages = $packages->keys()->values();

            $this->components->bulletList($packages->all());

            $this->composer->removePackages($packages->all(), dev: $group === 'dev');
        });
    }

    protected function hasRemovablePackages(array $packages): bool
    {
        $required = $this->getRequiredPackages();

        return collect($packages)->keys()
            ->filter(fn ($package) => $required->has($package))
            ->isNotEmpty();
    }

    protected function installPackages(array $packages): void
    {
        $installable = $this->getInstallablePackages($packages);

        if ($installable->isEmpty()) {
            return;
        }

        $packageGroups = $installable->groupBy(function ($group) {
            return $group;
        }, preserveKeys: true);

        $packageGroups->each(function ($packages, $group) {
            $packages = $packages->keys()->values();

            $this->components->bulletList($packages->all());

            $extra = ($group === 'dev') ? '--dev' : '';

            $this->composer->installPackages($packages->all(), $extra);
        });
    }

    protected function hasInstallablePackages(array $packages): bool
    {
        return $this->getInstallablePackages($packages)->isNotEmpty();
    }

    /**
     * @param  array<string, string>  $packages
     * @return Collection<string, string>
     */
    protected function getInstallablePackages(array $packages): Collection
    {
        $required = $this->getRequiredPackages();

        return collect($packages)->filter(function (string $group, string $package) use ($required) {
            $current = $required->get($package);

            if ($current === null) {
                return true;
            }

            return $group === 'require' && $current === 'dev';
        });
    }

    /**
     * @return Collection<string, string> Package name mapped to 'require' or 'dev'
     */
    protected function getRequiredPackages(): Collection
    {
        $file = $this->getComposerConfigPath();

        if (! file_exists($file)) {
            return collect();
        }

        $config = json_decode(file_get_contents($file), true) ?? [];

        return collect($config['require-dev'] ?? [])->keys()
            ->mapWithKeys(fn (string $package) => [$package => 'dev'])
            ->merge(
                collect($config['require'] ?? [])->keys()
                    ->mapWithKeys(fn (string $package) => [$package => 'require'])
            );
    }

    protected function getComposerConfigPath(): string
    {
        return base_path('composer.json');
    }
}
