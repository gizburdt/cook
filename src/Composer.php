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
        $currentScripts = $this->getScripts($hook);

        if (in_array($script, $currentScripts)) {
            return 0;
        }

        $currentScripts[] = $script;

        $command = array_merge(
            $this->findComposer(),
            ['config', "scripts.{$hook}"],
            $currentScripts
        );

        return $this->getProcess($command)->run();
    }

    public function getScripts(string $hook): array
    {
        $config = json_decode(file_get_contents($this->workingPath.'/composer.json'), true);

        $scripts = $config['scripts'][$hook] ?? [];

        return (array) $scripts;
    }
}
