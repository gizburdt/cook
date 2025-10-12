<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

use function Laravel\Prompts\multiselect;

class Packages extends Command
{
    protected $signature = 'cook:packages';

    protected $description = 'Install packages';

    protected $packages;

    public function __construct(Filesystem $files, protected Composer $composer)
    {
        parent::__construct($files);
    }

    public function handle(): void
    {
        $this->line('Installing these packages:');

        $this->components->bulletList($this->mandatory()->keys()->toArray());

        $this->packages = multiselect(
            'Which packages do you want to install?',
            options: $this->choices()->keys()->toArray(),
        );

        $this->info('Installing packages (require)');

        $this->installRequirePackages();

        $this->info('Installing packages (require-dev)');

        $this->installRequireDevPackages();

        $this->info('Done!');
    }

    protected function installRequirePackages(): void
    {
        $this->packages($this->packages, 'require')->each(function ($package) {
            $this->line($package);

            $this->composer->installPackages([$package]);
        });
    }

    protected function installRequireDevPackages(): void
    {
        $this->packages($this->packages, 'dev')->each(function ($package) {
            $this->line($package);

            $this->composer->installPackages([$package], '--dev');
        });
    }

    protected function packages(array $packages, string $scope): Collection
    {
        $packages = collect($packages)->flip();

        $choices = $this->choices()
            ->filter(fn ($value): bool => $value == $scope)
            ->intersectByKeys($packages);

        $mandatory = $this->mandatory()
            ->filter(fn ($value): bool => $value == $scope);

        return $mandatory->merge($choices)->keys();
    }

    protected function mandatory(): Collection
    {
        return collect([
            'barryvdh/laravel-debugbar' => 'dev',
            'canvural/larastan-strict-rules' => 'dev',
            'laracasts/presenter' => 'require',
            'larastan/larastan' => 'dev',
            'laravel/boost' => 'dev',
            'laravel/horizon' => 'require',
            'laravel/pail' => 'dev',
            'lorisleiva/laravel-actions' => 'require',
            'nunomaduro/essentials' => 'require',
            'nunomaduro/phpinsights' => 'dev',
            'pestphp/pest' => 'dev',
            'pestphp/pest-plugin-laravel' => 'dev',
            'pestphp/pest-plugin-livewire' => 'dev',
            'spatie/laravel-failed-job-monitor' => 'require',
            'spatie/pest-plugin-test-time' => 'dev',
            'predis/predis' => 'require',
            'rector/rector' => 'dev',
            'spatie/laravel-ray' => 'require',
        ]);
    }

    protected function choices(): Collection
    {
        return collect([
            'barryvdh/laravel-snappy' => 'require',
            'jenssegers/model' => 'require',
            'filament/filament' => 'require',
            'laravel/breeze' => 'require',
            'laravel/pulse' => 'require',
            'laravel/scout' => 'require',
            'laravel/telescope' => 'require',
            'livewire/livewire' => 'require',
            'maatwebsite/excel' => 'require',
            'spatie/cpu-load-health-check' => 'require',
            'spatie/laravel-backup' => 'require',
            'spatie/laravel-health' => 'require',
            'spatie/laravel-directory-cleanup' => 'require',
            'spatie/laravel-login-link' => 'require',
            'spatie/laravel-query-builder' => 'require',
            'spatie/simple-excel' => 'require',
            'staudenmeir/belongs-to-through' => 'require',
            'staudenmeir/eloquent-has-many-deep' => 'require',
            'staudenmeir/laravel-adjacency-list' => 'require',
            'symfony/postmark-mailer' => 'require',
        ]);
    }
}
