<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

use function Laravel\Prompts\select;

class Ui extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:ui {--force} {--skip-pint}';

    protected $description = 'Install UI';

    protected string $driver;

    protected ?string $docs = null;

    protected array $packages = [];

    public function handle(): void
    {
        $this->driver = select('What UI?', [
            'flux' => 'Flux',
        ]);

        $this->setupDriver();

        $this->tryInstallPackages();

        $this->runPint();

        $this->openDocs();
    }

    protected function setupDriver(): void
    {
        if ($this->driver === 'flux') {
            $this->packages['livewire/flux'] = 'require';

            $this->docs = 'https://fluxui.dev/docs/installation';
        }
    }
}
