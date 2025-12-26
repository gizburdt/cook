<?php

namespace Gizburdt\Cook\Commands\Concerns;

trait UsesCssParser
{
    protected function appendSourceDirectives(string $file, array $sources): void
    {
        if (! $this->files->exists($file)) {
            return;
        }

        $content = $this->files->get($file);

        $missingSources = collect($sources)
            ->reject(fn ($source) => str_contains($content, $source))
            ->implode("\n");

        if ($missingSources === '') {
            return;
        }

        $this->files->put($file, $content.$missingSources);
    }
}
