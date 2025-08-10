<?php

namespace Gizburdt\Cook;

trait ReplacesContent
{
    protected function replaceContent(array $replace, string $stub): string
    {
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }
}
