<?php

namespace Gizburdt\Cook\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Filesystem\Filesystem;

abstract class Command extends ConsoleCommand
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        parent::__construct();
    }

    abstract public function handle();

    abstract protected function after();
}
