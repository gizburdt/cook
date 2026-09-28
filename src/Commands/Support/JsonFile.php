<?php

namespace Gizburdt\Cook\Commands\Support;

use Illuminate\Filesystem\Filesystem;

class JsonFile
{
    protected array $data;

    protected bool $changed = false;

    public function __construct(
        protected Filesystem $files,
        protected string $path,
    ) {
        $this->data = $this->files->exists($path)
            ? json_decode($this->files->get($path), true) ?? []
            : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Appends the items to the list at the key, skipping items whose identity is already in it.
     *
     * @param  callable(mixed): mixed  $identity
     */
    public function mergeList(string $key, array $items, callable $identity): int
    {
        $list = $this->get($key, []);

        $existing = array_map($identity, $list);

        $added = 0;

        foreach ($items as $item) {
            if (in_array($identity($item), $existing, true)) {
                continue;
            }

            $list[] = $item;

            $existing[] = $identity($item);

            $added++;
        }

        if ($added > 0) {
            data_set($this->data, $key, $list);

            $this->changed = true;
        }

        return $added;
    }

    public function save(): void
    {
        if (! $this->changed) {
            return;
        }

        $this->files->ensureDirectoryExists(dirname($this->path));

        $this->files->put($this->path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->changed = false;
    }
}
