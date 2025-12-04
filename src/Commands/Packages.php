<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Illuminate\Support\Collection;

use function Laravel\Prompts\multiselect;

class Packages extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:packages {--force}';

    protected $description = 'Install packages';

    protected array $chosen = [];

    public function handle(): void
    {
        $this->chosen = multiselect(
            'Which packages do you want to install?',
            options: $this->possibilities()->keys()->toArray(),
        );

        $requirePackages = $this->selectPackages($this->chosen, 'require');

        if ($this->hasInstallablePackages($requirePackages)) {
            $this->components->info('Installing packages (require)');

            $this->installPackages($requirePackages);
        }

        $devPackages = $this->selectPackages($this->chosen, 'dev');

        if ($this->hasInstallablePackages($devPackages)) {
            $this->components->info('Installing packages (require-dev)');

            $this->installPackages($devPackages);
        }
    }

    protected function selectPackages(array $chosen, string $scope): array
    {
        $packages = collect($chosen)->flip();

        return $this->possibilities()
            ->filter(fn ($value): bool => $value == $scope)
            ->intersectByKeys($packages)
            ->toArray();
    }

    protected function possibilities(): Collection
    {
        return collect([
            'barryvdh/laravel-snappy' => 'require',
            'dutchcodingcompany/filament-developer-logins' => 'require',
            'jenssegers/model' => 'require',
            'laravel/breeze' => 'require',
            'laravel/pulse' => 'require',
            'laravel/scout' => 'require',
            'laravel/telescope' => 'require',
            'livewire/livewire' => 'require',
            'maatwebsite/excel' => 'require',
            'spatie/cpu-load-health-check' => 'require',
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
