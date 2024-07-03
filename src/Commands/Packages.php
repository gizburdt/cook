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

    protected $composer;

    public function __construct(Filesystem $files, Composer $composer)
    {
        $this->composer = $composer;

        parent::__construct($files);
    }

    public function handle()
    {
        $this->line('Installing these packages:');

        $this->components->bulletList($this->mandatory()->keys()->toArray());

        $packages = multiselect(
            'Which packages do you want to install?',
            options: $this->choices()->keys()->toArray(),
        );

        $this->info('Installing repositories...');

        $this->installRepositories();

        $this->info('Installing packages...');

        $this->installPackages($packages);

        $this->info('Done!');

        return Command::SUCCESS;
    }

    protected function installRepositories()
    {
        $this->composer->addRepository('nova', 'composer', 'https://nova.laravel.com');
    }

    protected function installPackages($packages)
    {
        $this->composer->installPackages(
            $this->packages($packages, 'require')
        );

        $this->composer->installPackages(
            $this->packages($packages, 'dev'), '--dev'
        );
    }

    protected function packages($packages, $scope): array
    {
        $packages = collect($packages)->flip();

        $choices = $this->choices()
            ->filter(fn ($value) => $value == $scope)
            ->intersectByKeys($packages);

        $mandatory = $this->mandatory()
            ->filter(fn ($value) => $value == $scope);

        return $mandatory->merge($choices)->keys()->toArray();
    }

    protected function mandatory(): Collection
    {
        return collect([
            'barryvdh/laravel-debugbar' => 'dev',
            'canvural/larastan-strict-rules' => 'dev',
            'laracraft-tech/laravel-date-scopes' => 'require',
            'larastan/larastan' => 'dev',
            'laravel/horizon' => 'require',
            'laravel/slack-notification-channel' => 'require',
            'laravel-lang/common' => 'dev',
            'nunomaduro/phpinsights' => 'dev',
            'predis/predis' => 'require',
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
            'laravel/nova' => 'require',
            'laravel/scout' => 'require',
            'laravel/telescope' => 'require',
            'livewire/livewire' => 'require',
            'maatwebsite/excel' => 'require',
            'maatwebsite/laravel-nova-excel' => 'require',
            'spatie/cpu-load-health-check' => 'require',
            'spatie/laravel-health' => 'require',
            'spatie/laravel-directory-cleanup' => 'require',
            'spatie/laravel-failed-job-monitor' => 'require',
            'spatie/laravel-login-link' => 'require',
            'spatie/laravel-model-status' => 'require',
            'spatie/simple-excel' => 'require',
            'staudenmeir/belongs-to-through' => 'require',
            'staudenmeir/eloquent-has-many-deep' => 'require',
            'symfony/postmark-mailer' => 'require',
        ]);
    }
}
