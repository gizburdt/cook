<?php

namespace Gizburdt\Cook;

trait ReplaceStubs
{
    protected function replaceStubs($replace, $stub): string
    {
        return str_replace(
            array_keys($replace), array_values($replace), $stub
        );
    }
}
