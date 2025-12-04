<?php

namespace Gizburdt\Cook;

use Illuminate\Support\Composer as BaseComposer;

class Composer extends BaseComposer
{
    public function installPackages(
        array $packages,
        string $extra = ''
    ): int {
        $extra = $extra ? (array) $extra : [];

        $packages = collect($packages)->prepend('require')->toArray();

        $command = array_merge($this->findComposer(), $packages, $extra);

        return $this->getProcess($command)->run();
    }

    public function addScript(string $hook, string $script): int
    {
        $command = array_merge(
            $this->findComposer(),
            ['config', "scripts.{$hook}", $script, '--merge']
        );

        return $this->getProcess($command)->run();
    }
}
