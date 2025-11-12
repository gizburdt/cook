<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Composer;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;

abstract class Command extends ConsoleCommand
{
    public function __construct(
        protected Filesystem $files,
        protected Composer $composer
    ) {
        parent::__construct();
    }

    abstract public function handle();

    protected function openDocs(): void
    {
        if (! isset($this->docs)) {
            return;
        }

        if (confirm('Open docs?', default: false)) {
            $process = new Process(['open', $this->docs]);

            $process->run();
        }
    }
}
