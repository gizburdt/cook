<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

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

        $packages = $this->choice(
            'Which packages do you want to install?',
            $this->choices()->keys()->toArray(),
            default: null,
            attempts: null,
            multiple: true,
        );

        $this->info('Installing packages...');

        $this->installPackages($packages);

        $this->info('Done!');

        return Command::SUCCESS;
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
            'laracraft-tech/laravel-date-scopes' => 'require',
            'laravel/horizon' => 'require',
            'laravel/slack-notification-channel' => 'require',
            'laravel-shift/blueprint' => 'dev',
            'nunomaduro/larastan' => 'dev',
            'nunomaduro/phpinsights' => 'dev',
            'predis/predis' => 'require',
            'spatie/laravel-ray' => 'require',
            'spatie/once' => 'require',
        ]);
    }

    protected function choices(): Collection
    {
        return collect([
            'barryvdh/laravel-snappy' => 'require',
            'coderello/laravel-nova-lang' => 'require',
            'laravel/breeze' => 'require',
            'laravel/nova' => 'require',
            'laravel/scout' => 'require',
            'laravel/telescope' => 'require',
            'livewire/livewire' => 'require',
            'maatwebsite/excel' => 'require',
            'spatie/cpu-load-health-check' => 'require',
            'spatie/laravel-health' => 'require',
            'spatie/laravel-directory-cleanup' => 'require',
            'spatie/laravel-failed-job-monitor' => 'require',
            'spatie/laravel-login-link' => 'require',
            'spatie/laravel-model-status' => 'require',
            'staudenmeir/belongs-to-through' => 'require',
            'staudenmeir/eloquent-has-many-deep' => 'require',
            'symfony/postmark-mailer' => 'require',
        ]);
    }
}
