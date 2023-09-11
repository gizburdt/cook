<?php

namespace Gizburdt\Cook;

use Illuminate\Support\Composer as BaseComposer;

class Composer extends BaseComposer
{
    public function addRepository($name, $type = 'composer', $repository = null)
    {
        $command = array_merge($this->findComposer(), [
            "config repositories.{$name} {$type} {$repository}",
        ]);

        return $this->getProcess($command)->run();
    }

    public function installPackages($packages, $extra = '')
    {
        $extra = $extra ? (array) $extra : [];

        $packages = collect($packages)->prepend('require')->toArray();

        $command = array_merge($this->findComposer(), $packages, $extra);

        return $this->getProcess($command)->run();
    }
}
