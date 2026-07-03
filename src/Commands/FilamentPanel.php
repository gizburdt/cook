<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesCssParser;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddAdminPanelProvider;
use Gizburdt\Cook\Commands\NodeVisitors\AddMultiFactorAuthentication;
use Gizburdt\Cook\Commands\Support\MfaMethodDetector;
use Gizburdt\Cook\Enums\MfaMethod;

class FilamentPanel extends Command
{
    use InstallsPackages;
    use UsesCssParser;
    use UsesPhpParser;

    protected $signature = 'cook:filament:panel {--force} {--skip-pint}';

    protected $description = 'Install Filament panel';

    public string $publishGroup = 'filament-panel';

    public array $publishes = [
        'Providers/AdminPanelProvider.php' => 'app/Providers/Filament/AdminPanelProvider.php',
        'resources/views/user-menu-before.blade.php' => 'resources/views/filament/user-menu-before.blade.php',
    ];

    protected array $packages = [
        'dutchcodingcompany/filament-developer-logins' => 'require',
        'filament/filament' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-filament-panel',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->installAdminPanel();

        $this->installFilamentTheme();

        $this->runPint();
    }

    protected function installAdminPanel(): void
    {
        $this->applyPhpVisitors(base_path('bootstrap/providers.php'), [
            AddAdminPanelProvider::class,
        ]);

        $methods = $this->detectMfaMethods();

        if (! empty($methods)) {
            $this->applyPhpVisitors(
                app_path('Providers/Filament/AdminPanelProvider.php'),
                [new AddMultiFactorAuthentication($methods)]
            );
        }

        $this->callInNewProcess('filament:upgrade');
    }

    /**
     * @return array<int, MfaMethod>
     */
    protected function detectMfaMethods(): array
    {
        $file = app_path('Models/User.php');

        if (! $this->files->exists($file)) {
            return [];
        }

        return MfaMethodDetector::fromContent($this->files->get($file));
    }

    protected function installFilamentTheme(): void
    {
        $this->callInNewProcess('filament:theme', [
            'panel' => 'admin',
        ]);

        // CSS
        $this->appendSourceDirectives(resource_path('css/filament/admin/theme.css'), [
            "@source '../../../../app/Filament/**/*';",
            "@source '../../../../resources/views/filament/**/*';",
            "@source '../../../../resources/views/livewire/**/*';",
        ]);

        // NPM
        $this->runInNewProcess('npm run build');
    }
}
