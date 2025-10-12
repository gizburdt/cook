<?php

namespace Gizburdt\Cook\Commands\Concerns;

trait InstallsPackages
{
    protected function installPackages(array $packages): void
    {
        collect($packages)->each(function ($type, $package) {
            $this->components->line($package);

            $extra = ($type === 'dev') ? '--dev' : '';

            $this->composer->installPackages([$package], $extra);
        });
    }
}
