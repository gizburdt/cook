<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddUserMenuItems;

it('adds userMenuItems after profile in the panel method chain', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->profile(EditProfile::class)
            ->middleware([]);
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddUserMenuItems::class,
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toContain("->profile(EditProfile::class)\n            ->userMenuItems([\n                Action::make('api-tokens')\n                    ->label(__('API tokens'))\n                    ->url(fn(): string => ApiTokens::getUrl())\n                    ->icon(Heroicon::OutlinedKey),\n            ])")
        ->toMatch('/->userMenuItems\(.*->middleware\(/s');
});

it('adds userMenuItems at end when profile is not present', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin');
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddUserMenuItems::class,
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toContain('->userMenuItems(')
        ->toMatch('/->path\(.*->userMenuItems\(/s');
});

it('does not duplicate userMenuItems if it already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ApiTokens;
use Filament\Actions\Action;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->profile()
            ->userMenuItems([
                Action::make('api-tokens')
                    ->label(__('API tokens'))
                    ->url(fn (): string => ApiTokens::getUrl())
                    ->icon(Heroicon::OutlinedKey),
            ])
            ->middleware([]);
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddUserMenuItems::class,
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect(substr_count($result, 'userMenuItems'))
        ->toBe(1);
});

it('adds required use statements', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->profile();
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddUserMenuItems::class,
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toContain('use Filament\Actions\Action;')
        ->toContain('use App\Filament\Pages\ApiTokens;')
        ->toContain('use Filament\Support\Icons\Heroicon;');
});

it('does not duplicate use statements if already exist', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ApiTokens;
use Filament\Actions\Action;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->profile();
    }
}
PHP;

    $result = $parser->testParseContent($content, [
        AddUserMenuItems::class,
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect(substr_count($result, 'use Filament\Actions\Action;'))
        ->toBe(1)
        ->and(substr_count($result, 'use App\Filament\Pages\ApiTokens;'))
        ->toBe(1)
        ->and(substr_count($result, 'use Filament\Support\Icons\Heroicon;'))
        ->toBe(1);
});
