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

        // todo: use composer config scripts.x "value"
        return $this->setScripts($hook, $currentScripts);
    }

    public function getScripts(string $hook): array
    {
        $config = $this->getComposerConfig();

        $scripts = $config['scripts'][$hook] ?? [];

        return (array) $scripts;
    }

    protected function setScripts(string $hook, array $scripts): int
    {
        $config = $this->getComposerConfig();

        $config['scripts'][$hook] = $scripts;

        return $this->writeComposerConfig($config);
    }

    protected function getComposerConfig(): array
    {
        if (! file_exists($path = "{$this->workingPath}/composer.json")) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    protected function writeComposerConfig(array $config): int
    {
        $result = file_put_contents(
            "{$this->workingPath}/composer.json",
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        return $result === false ? 1 : 0;
    }
}
