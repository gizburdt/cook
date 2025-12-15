<?php

namespace Gizburdt\Cook\Commands\Concerns;

trait UsesEnvParser
{
    protected function addEnvVariables(array $variables): void
    {
        $this->addEnvVariablesToFile(base_path('.env'), $variables);

        $this->addEnvVariablesToFile(base_path('.env.example'), $variables);
    }

    protected function addEnvVariablesToFile(string $file, array $variables): void
    {
        if (! $this->files->exists($file)) {
            return;
        }

        $content = $this->files->get($file);

        $newVariables = collect($variables)
            ->reject(fn ($value, $key) => is_int($key))
            ->reject(fn ($value, $key) => $value === null)
            ->reject(fn ($value, $key) => $this->hasEnvVariable($content, $key))
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        if ($newVariables !== '') {
            $content = rtrim($content)."\n\n{$newVariables}\n";
        }

        $this->files->put($file, $content);
    }

    protected function hasEnvVariable(string $content, string $key): bool
    {
        return preg_match('/^'.preg_quote($key, '/').'=/m', $content) === 1;
    }
}
