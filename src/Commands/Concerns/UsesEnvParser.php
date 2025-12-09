<?php

namespace Gizburdt\Cook\Commands\Concerns;

trait UsesEnvParser
{
    protected function addEnvVariables(array $variables): void
    {
        $this->addVariablesToFile(base_path('.env'), $variables);

        $this->addVariablesToFile(base_path('.env.example'), $variables);
    }

    protected function addVariablesToFile(string $file, array $variables): void
    {
        if (! $this->files->exists($file)) {
            return;
        }

        $content = $this->files->get($file);

        $newVariables = collect($variables)
            ->map(function ($value, $key) use ($content) {
                if ($value === null || is_int($key)) {
                    return '';
                }

                if (! $this->hasVariable($content, $key)) {
                    return "{$key}={$value}";
                }

                return null;
            })
            ->filter()
            ->implode("\n");

        if ($newVariables !== '') {
            $content = rtrim($content)."\n\n{$newVariables}\n";
        }

        $this->files->put($file, $content);
    }

    protected function hasVariable(string $content, string $key): bool
    {
        return preg_match('/^'.preg_quote($key, '/').'=/m', $content) === 1;
    }
}
