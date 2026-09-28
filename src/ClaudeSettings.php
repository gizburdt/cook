<?php

namespace Gizburdt\Cook;

use Illuminate\Filesystem\Filesystem;

class ClaudeSettings
{
    public function __construct(
        protected Filesystem $files,
        protected string $path,
    ) {}

    /**
     * Adds hook groups to the settings, skipping groups whose command is already there.
     *
     * @param  array<string, list<array{matcher?: string, hooks: list<array{type: string, command: string, timeout?: int}>}>>  $hooks
     */
    public function mergeHooks(array $hooks): int
    {
        $settings = $this->read();

        $added = 0;

        foreach ($hooks as $event => $groups) {
            $current = $settings['hooks'][$event] ?? [];

            $commands = collect($current)->pluck('hooks')->flatten(1)->pluck('command');

            foreach ($groups as $group) {
                if (collect($group['hooks'])->pluck('command')->intersect($commands)->isNotEmpty()) {
                    continue;
                }

                $current[] = $group;

                $added++;
            }

            $settings['hooks'][$event] = $current;
        }

        if ($added > 0) {
            $this->write($settings);
        }

        return $added;
    }

    protected function read(): array
    {
        if (! $this->files->exists($this->path)) {
            return [];
        }

        return json_decode($this->files->get($this->path), true) ?? [];
    }

    protected function write(array $settings): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path));

        $this->files->put($this->path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }
}
