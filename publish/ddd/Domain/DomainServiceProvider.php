<?php

namespace App\Domain;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
use ReflectionClass;

abstract class DomainServiceProvider extends ServiceProvider
{
    protected static array $domainNamespaces = [];

    protected string $path;

    protected string $namespace;

    protected string $name;

    protected static array $facades = [];

    protected static array $bladeComponents = [];

    protected static array $livewireComponents = [];

    protected static array $viewComposers = [];

    protected string|array $webMiddleware = 'web';

    protected string|array $apiMiddleware = 'api';

    protected ?string $routeDomain = null;

    protected ?string $webRouteName = null;

    protected ?string $apiRouteName = null;

    public function register(): void
    {
        $this->registerFacades();
    }

    public function boot(): void
    {
        $this->initialize();

        $this->bootPolicies();

        $this->bootBladeComponents();

        $this->bootBladeDirectives();

        $this->bootComponentAliases();

        $this->bootViewComposers();

        $this->bootLivewireComponents();

        $this->bootCommands();

        $this->bootRoutes();
    }

    protected function initialize(): void
    {
        $reflector = new ReflectionClass(static::class);

        $this->path = dirname($reflector->getFileName(), 2);

        $this->namespace = Str::of($reflector->getNamespaceName())
            ->before('\\Providers')
            ->toString();

        $this->name = Str::of(class_basename($this->namespace))
            ->kebab()
            ->toString();
    }

    protected function registerFacades(): void
    {
        foreach (static::$facades as $bind => $class) {
            $this->app->singleton($bind, fn () => new $class);
        }
    }

    protected function bootPolicies(): void
    {
        static::$domainNamespaces[] = $this->namespace;

        Gate::guessPolicyNamesUsing(function ($modelClass) {
            $basename = class_basename($modelClass);

            foreach (static::$domainNamespaces as $namespace) {
                $domainPolicy = "{$namespace}\\Policies\\{$basename}Policy";

                if (class_exists($domainPolicy)) {
                    return $domainPolicy;
                }
            }

            return "App\\Policies\\{$basename}Policy";
        });
    }

    protected function bootBladeComponents(): void
    {
        foreach (static::$bladeComponents as $bind => $class) {
            Blade::component($bind, $class);
        }
    }

    /**
     * Hook for domain specific Blade directives.
     */
    protected function bootBladeDirectives(): void
    {
        //
    }

    protected function bootComponentAliases(): void
    {
        $base = resource_path("views/{$this->name}/components");

        if (! File::isDirectory($base)) {
            return;
        }

        foreach (File::allFiles($base) as $file) {
            $relative = Str::after($file->getPathname(), $base.DIRECTORY_SEPARATOR);

            $name = Str::of($relative)
                ->beforeLast('.blade.php')
                ->replace(DIRECTORY_SEPARATOR, '.')
                ->toString();

            $alias = "{$this->name}.{$name}";

            if (array_key_exists($alias, static::$bladeComponents)) {
                continue;
            }

            Blade::component("{$this->name}.components.{$name}", $alias);
        }
    }

    protected function bootViewComposers(): void
    {
        foreach (static::$viewComposers as $bind => $class) {
            View::composer($bind, $class);
        }
    }

    protected function bootLivewireComponents(): void
    {
        foreach (static::$livewireComponents as $bind => $class) {
            Livewire::component($bind, $class);
        }
    }

    protected function bootCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $commands = collect(File::glob("{$this->path}/Commands/*.php"))
            ->map(fn (string $file) => "{$this->namespace}\\Commands\\".basename($file, '.php'))
            ->filter(fn (string $class) => is_subclass_of($class, Command::class))
            ->values()
            ->toArray();

        if ($commands !== []) {
            $this->commands($commands);
        }
    }

    protected function bootRoutes(): void
    {
        $this->routes('web.php', $this->webMiddleware, $this->webRouteName ?? "{$this->name}.");

        $this->routes('api.php', $this->apiMiddleware, $this->apiRouteName ?? "{$this->name}.api.");
    }

    protected function routes(string $file, string|array $middleware, string $name): void
    {
        $path = "{$this->path}/routes/{$file}";

        if (! File::exists($path)) {
            return;
        }

        $route = Route::middleware($middleware)
            ->name($name);

        if ($domain = $this->routeDomain ?? config("app.domains.{$this->name}")) {
            $route->domain($domain);
        }

        $route->group($path);
    }
}
