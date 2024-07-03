<?php

namespace Gizburdt\Cook;

use Illuminate\Support\Composer as BaseComposer;

class Composer extends BaseComposer
{
    public function addRepository($name, $type = 'composer', $url = null)
    {
        $command = array_merge($this->findComposer(), [
            trim("config repositories.{$name} {$type} {$url}"),
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
