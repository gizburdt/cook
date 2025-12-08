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

        foreach ($variables as $key => $value) {
            if (! $this->envHasVariable($content, $key)) {
                $content = rtrim($content)."\n\n{$key}={$value}\n";
            }
        }

        $this->files->put($file, $content);
    }

    protected function envHasVariable(string $content, string $key): bool
    {
        return preg_match('/^'.preg_quote($key, '/').'=/m', $content) === 1;
    }
}
