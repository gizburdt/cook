<?php

namespace Gizburdt\Cook\Commands\Concerns;

trait InstallsPackages
{
    protected function installPackages(array $packages): void
    {
        collect($packages)->groupBy(function ($type) {
            return $type;
        }, preserveKeys: true)->each(function ($packages, $type) {
            $packages = $packages->keys()->all();

            $this->components->bulletList($packages);

            $extra = ($type === 'dev') ? '--dev' : '';

            $this->composer->installPackages($packages, $extra);
        });
    }
}
