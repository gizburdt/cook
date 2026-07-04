<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\InstallsPassport;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;

class Api extends Command
{
    use InstallsPackages;
    use InstallsPassport;
    use UsesPhpParser;

    protected $signature = 'cook:api {--force} {--skip-pint}';

    protected $description = 'Install API';

    protected string $docs = 'https://laravel.com/docs/12.x/passport';

    public string $publishGroup = 'api';

    public array $publishes = [
        //
    ];

    protected array $packages = [
        //
    ];

    public function handle(): void
    {
        $this->installPassport();

        $this->runPint();

        $this->openDocs();
    }
}
