<?php

namespace Gizburdt\Cook\Commands;

class Ddd extends Command
{
    protected $signature = 'cook:ddd {--force} {--skip-pint}';

    protected $description = 'Install domain driven development';

    public string $publishGroup = 'ddd';

    public array $publishes = [
        'Domain/DomainServiceProvider.php' => 'app/Domain/DomainServiceProvider.php',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-ddd',
            '--force' => $this->option('force'),
        ]);

        $this->runPint();
    }
}
