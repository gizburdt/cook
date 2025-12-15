<?php

namespace Gizburdt\Cook;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Composer as BaseComposer;

class Composer extends BaseComposer
{
    public function installPackages(array $packages, string $extra = ''): int
    {
        $command = collect($this->findComposer())
            ->push('require')
            ->push($packages)
            ->push(Arr::wrap($extra))
            ->flatten()
            ->filter(fn ($value) => $value !== '')
            ->toArray();

        return $this->getProcess($command)->run();
    }

    public function addScript(string $hook, string $script): int
    {
        return $this->addToConfig("scripts.{$hook}", $script);
    }

    public function addAutoloadFile(string $file): int
    {
        return $this->addToConfig('autoload.files', $file);
    }

    protected function addToConfig(string $key, string $value): int
    {
        $config = $this->getComposerConfig();

        $current = collect(data_get($config, $key) ?? []);

        if ($current->doesntContain($value)) {
            $current->push($value);

            data_set($config, $key, $current->toArray());

            return $this->writeComposerConfig($config);
        }

        return 0;
    }

    protected function getFromConfig(string $key): Collection
    {
        $config = $this->getComposerConfig();

        return collect(data_get($config, $key) ?? []);
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
