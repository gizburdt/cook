<?php

namespace Gizburdt\Cook\Commands\Concerns;

trait BuildsArtisanCommands
{
    protected function buildArtisanCommand(string $command, array $arguments = []): string
    {
        $args = collect($arguments)
            ->map(fn ($value, $key) => is_numeric($key) ? $value : "--{$key}={$value}")
            ->implode(' ');

        return trim("php artisan {$command} {$args}");
    }
}
