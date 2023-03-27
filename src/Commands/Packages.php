<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Composer;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class Packages extends Command
{
    protected $signature = 'cook:packages';

    protected $description = 'Install packages';

    protected $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->composer->dumpAutoloads();

        $packages = $this->choice(
            'Which packages do you want to install?',
            $this->choices()->keys()->toArray(),
            default: null,
            attempts: null,
            multiple: true,
        );

        $this->info('Installing packages...');

        $this->composer->installPackages(
            $this->packages($packages, 'require')
        );

        $this->composer->installPackages(
            $this->packages($packages, 'dev'), '--dev'
        );

        $this->info('Done!');

        return Command::SUCCESS;
    }

    protected function packages($packages, $scope)
    {
        return $this->choices()
            ->filter(fn ($value) => $value == $scope)
            ->keys()
            ->intersect($packages)
            ->toArray();
    }

    protected function choices(): Collection
    {
        return collect([
            'barryvdh/laravel-snappy' => 'require',
            'coderello/laravel-nova-lang' => 'require',
            'laravel/breeze' => 'require',
            'laravel/horizon' => 'require',
            'laravel/nova' => 'require',
            'laravel/slack-notification-channel' => 'require',
            'laravel-shift/blueprint' => 'dev',
            'livewire/livewire' => 'require',
            'maatwebsite/excel' => 'require',
            'spatie/cpu-load-health-check' => 'require',
            'spatie/laravel-health' => 'require',
            'spatie/laravel-directory-cleanup' => 'require',
            'spatie/laravel-failed-job-monitor' => 'require',
            'spatie/laravel-login-link' => 'require',
            'spatie/laravel-model-status' => 'require',
            'spatie/laravel-ray' => 'require',
            'spatie/once' => 'require',
            'staudenmeir/belongs-to-through' => 'require',
            'staudenmeir/eloquent-has-many-deep' => 'require',
            'symfony/postmark-mailer' => 'require',
        ]);
    }
}
