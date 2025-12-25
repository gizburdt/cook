<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddAdminPanelProvider;

class FilamentPanel extends Command
{
    use InstallsPackages;
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
        $this->appendSourceDirectives();

        // NPM
        $this->runInNewProcess('npm run build');
    }

    protected function appendSourceDirectives(): void
    {
        $themePath = resource_path('css/filament/admin/theme.css');

        if (! file_exists($themePath)) {
            return;
        }

        $content = file_get_contents($themePath);

        $sources = [
            "@source '../../../../app/Filament/**/*';",
            "@source '../../../../resources/views/filament/**/*';",
        ];

        $missingSources = array_filter($sources, fn ($source) => ! str_contains($content, $source));

        if (empty($missingSources)) {
            return;
        }

        $content .= implode("\n", $missingSources);

        file_put_contents($themePath, $content);
    }
}
