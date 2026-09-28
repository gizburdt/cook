<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Support\JsonFile;

class Ai extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:ai {--force} {--skip-pint}';

    protected $description = 'Install AI';

    public string $publishGroup = 'ai';

    public array $publishes = [
        '.ai' => '.ai',
        '.claude/hooks' => '.claude/hooks',
        '.claude/settings.local.json' => '.claude/settings.local.json',
    ];

    protected array $packages = [
        'laravel/boost' => 'dev',
        'laravel/pao' => 'dev',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-ai',
            '--force' => $this->option('force'),
        ]);

        $this->addClaudeHooks();

        $this->tryInstallPackages();

        $this->callInNewProcess('boost:install');

        $this->components->info('Updating composer.json');

        $this->composer->addScript('post-update-cmd', '@php artisan boost:update --ansi');

        $this->runPint();
    }

    protected function addClaudeHooks(): void
    {
        $this->components->info('Adding Claude Code hooks');

        $source = new JsonFile($this->files, __DIR__.'/../../publish/ai/.claude/settings.json');

        $settings = new JsonFile($this->files, base_path('.claude/settings.json'));

        foreach ($source->get('hooks') as $event => $groups) {
            $settings->mergeList("hooks.{$event}", $groups, fn (array $group) => array_column($group['hooks'], 'command'));
        }

        $settings->save();
    }
}
