<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesCssParser;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddAdminPanelProvider;

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
    }

    protected function installAdminPanel(): void
    {
        $this->applyPhpVisitors(base_path('bootstrap/providers.php'), [
            AddAdminPanelProvider::class,
        ]);

        $this->callInNewProcess('filament:upgrade');
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
