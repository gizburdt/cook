<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Composer;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Filesystem\Filesystem;

abstract class Command extends ConsoleCommand
{
    public function __construct(
        protected Filesystem $files,
        protected Composer $composer
    ) {
        parent::__construct();
    }

    abstract public function handle();
}
