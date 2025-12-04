<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class Ui extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:ui {--force}';

    protected $description = 'Install UI';

    protected ?string $docs = null;

    protected array $packages = [];

    public function handle(): void
    {
        $mode = select('What UI?', [
            'flux' => 'Flux',
        ]);

        $this->setup($mode);

        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }

        $this->openDocs();
    }

    protected function setup(string $mode): void
    {
        $method = Str::of($mode)->studly()->prepend('setup')->toString();

        if (method_exists($this, $method)) {
            $this->$method();
        }
    }

    protected function setupFlux(): void
    {
        $this->packages = [
            'livewire/flux' => 'require',
        ];

        $this->docs = 'https://fluxui.dev/docs/installation';
    }
}
