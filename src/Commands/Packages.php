<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Illuminate\Support\Collection;

use function Laravel\Prompts\multiselect;

class Packages extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:packages';

    protected $description = 'Install packages';

    protected array $chosen = [];

    public function handle(): void
    {
        $this->chosen = multiselect(
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
        $this->installPackages(
            $this->selectPackages($this->chosen, 'require')
        );
    }

    protected function installRequireDevPackages(): void
    {
        $this->installPackages(
            $this->selectPackages($this->chosen, 'dev')
        );
    }

    protected function selectPackages(array $chosen, string $scope): Collection
    {
        return $this->possibilities()
            ->filter(fn ($value): bool => $value == $scope)
            ->intersectByKeys(array_values($chosen));
    }

    protected function possibilities(): Collection
    {
        return collect([
            'barryvdh/laravel-snappy' => 'require',
            'dutchcodingcompany/filament-developer-logins' => 'require',
            'jenssegers/model' => 'require',
            'filament/filament' => 'require',
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
