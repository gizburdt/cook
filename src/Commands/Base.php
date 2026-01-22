<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddLocalRoutes;
use Gizburdt\Cook\Commands\NodeVisitors\AddOptimizes;
use Gizburdt\Cook\Commands\NodeVisitors\AddPasswordRules;
use Gizburdt\Cook\Commands\NodeVisitors\RemoveEloquentModel;
use Gizburdt\Cook\Commands\NodeVisitors\RemoveHealthRoute;
use Illuminate\Support\Str;

class Base extends Command
{
    use InstallsPackages;
    use UsesPhpParser;

    protected $signature = 'cook:base {--force} {--skip-pint}';

    protected $description = 'Install base';

    public string $publishGroup = 'base';

    public array $publishes = [
        '.github' => '.github',
        'stubs' => 'stubs',
        'Actions/Action.php' => 'app/Actions/Action.php',
        'Console/Commands/Optimize.php' => 'app/Console/Commands/Optimize.php',
        'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
        'Models/Model.php' => 'app/Models/Model.php',
        'Models/Pivot.php' => 'app/Models/Pivot.php',
        'Models/Concerns' => 'app/Models/Concerns',
        'Policies/Policy.php' => 'app/Policies/Policy.php',
        'Support/helpers.php' => 'app/Support/helpers.php',
        'routes/local.php' => 'routes/local.php',
    ];

    protected array $packages = [
        'barryvdh/laravel-debugbar' => 'dev',
        'laracasts/presenter' => 'require',
        'laravel/horizon' => 'require',
        'laravel/pail' => 'dev',
        'laravel/prompts' => 'require',
        'lorisleiva/laravel-actions' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-base',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->components->info('Updating composer.json');

        $this->composer->addAutoloadFile('app/Support/helpers.php');

        $this->components->info('Replacing Eloquent Model');

        $this->replaceEloquentModel();

        $this->components->info('Adding password rules');

        $this->addPasswordRules();

        $this->components->info('Adding optimizes');

        $this->addOptimizes();

        $this->components->info('Adding local routes');

        $this->addLocalRoutes();

        $this->components->info('Removing health route');

        $this->removeHealthRoute();
    }

    protected function replaceEloquentModel(): void
    {
        $files = $this->files->glob(
            $this->laravel->basePath('app/Models/*.php')
        );

        $files = collect($files)->reject(function ($file) {
            return Str::of($file)->contains(['Model.php', 'Pivot.php']);
        })->toArray();

        $this->withProgressBar($files, function ($file) {
            $content = $this->files->get($file);

            $content = $this->parsePhpContent($content, [
                RemoveEloquentModel::class,
            ]);

            $this->files->put($file, $content);
        });

        $this->line("\n");
    }

    protected function addPasswordRules(): void
    {
        $this->applyPhpVisitors(app_path('Providers/AppServiceProvider.php'), [
            AddPasswordRules::class,
        ]);
    }

    protected function addOptimizes(): void
    {
        $this->applyPhpVisitors(app_path('Providers/AppServiceProvider.php'), [
            AddOptimizes::class,
        ]);
    }

    protected function addLocalRoutes(): void
    {
        $this->applyPhpVisitors(base_path('bootstrap/app.php'), [
            AddLocalRoutes::class,
        ]);
    }

    protected function removeHealthRoute(): void
    {
        $this->applyPhpVisitors(base_path('bootstrap/app.php'), [
            RemoveHealthRoute::class,
        ]);

        $this->runPint();
    }
}
