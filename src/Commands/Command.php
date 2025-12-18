<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Composer;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

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

    protected function runPint(): void
    {
        if ($this->hasOption('skip-pint') && $this->option('skip-pint')) {
            return;
        }

        $this->components->info('Running Pint');

        Process::path(base_path())->run('vendor/bin/pint --dirty');
    }

    protected function callInNewProcess($command): bool
    {
        $result = Process::path(base_path())->tty()
            ->run("php artisan {$command}");

        return ! $result->failed();
    }

    protected function openDocs(): void
    {
        if (! isset($this->docs)) {
            return;
        }

        if (confirm('Open docs?', default: false)) {
            Process::path(base_path())->run(['open', $this->docs]);
        }
    }
}
