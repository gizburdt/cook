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

        $removable = $this->getRemovablePackages($this->removePackages);

        if ($removable->isEmpty()) {
            return;
        }

        $this->components->info('Removing packages');

        $removable->groupBy(fn (string $group) => $group, preserveKeys: true)
            ->each(function (Collection $packages, string $group) {
                $packages = $packages->keys()->all();

                $this->components->bulletList($packages);

                $this->composer->removePackages($packages, dev: $group === 'dev');
            });
    }

    protected function installPackages(array $packages): void
    {
        $installable = $this->getInstallablePackages($packages);

        if ($installable->isEmpty()) {
            return;
        }

        $installable->groupBy(fn (string $group) => $group, preserveKeys: true)
            ->each(function (Collection $packages, string $group) {
                $packages = $packages->keys()->all();

                $this->components->bulletList($packages);

                $this->composer->requirePackages($packages, dev: $group === 'dev');
            });
    }

    protected function hasInstallablePackages(array $packages): bool
    {
        return $this->getInstallablePackages($packages)->isNotEmpty();
    }

    protected function hasRemovablePackages(array $packages): bool
    {
        return $this->getRemovablePackages($packages)->isNotEmpty();
    }

    /**
     * @param  array<string, string>  $packages
     * @return Collection<string, string>
     */
    protected function getInstallablePackages(array $packages): Collection
    {
        $required = $this->composer->getRequiredPackages();

        return collect($packages)->filter(fn (string $group, string $package) => match ($required->get($package)) {
            null => true,
            'dev' => $group === 'require',
            default => false,
        });
    }

    /**
     * @param  array<string, string>  $packages
     * @return Collection<string, string> Package name mapped to the section it is required in
     */
    protected function getRemovablePackages(array $packages): Collection
    {
        return $this->composer->getRequiredPackages()->only(array_keys($packages));
    }
}
