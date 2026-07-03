<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddMultiFactorAuthentication;
use Gizburdt\Cook\Enums\MfaMethod;

function panelProviderStub(): string
{
    return <<<'PHP'
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
            ->login()
            ->profile(EditProfile::class)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
PHP;
}

it('adds app multi factor authentication', function () {
    $result = createPhpParserHelper()->testParseContent(panelProviderStub(), [
        new AddMultiFactorAuthentication([MfaMethod::App]),
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toContain('->multiFactorAuthentication([')
        ->toContain('AppAuthentication::make()')
        ->toContain('->recoverable()')
        ->toContain('isRequired: app()->isProduction()')
        ->toContain('use Filament\Auth\MultiFactor\App\AppAuthentication;');
});

it('adds email multi factor authentication without recoverable', function () {
    $result = createPhpParserHelper()->testParseContent(panelProviderStub(), [
        new AddMultiFactorAuthentication([MfaMethod::Email]),
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toContain('EmailAuthentication::make()')
        ->toContain('use Filament\Auth\MultiFactor\Email\EmailAuthentication;')
        ->not->toContain('->recoverable()')
        ->not->toContain('AppAuthentication');
});

it('adds both entries when both selected', function () {
    $result = createPhpParserHelper()->testParseContent(panelProviderStub(), [
        new AddMultiFactorAuthentication([MfaMethod::App, MfaMethod::Email]),
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toContain('AppAuthentication::make()')
        ->toContain('EmailAuthentication::make()')
        ->toContain('use Filament\Auth\MultiFactor\App\AppAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\Email\EmailAuthentication;');
});

it('adds nothing when no methods selected', function () {
    $result = createPhpParserHelper()->testParseContent(panelProviderStub(), [
        new AddMultiFactorAuthentication([]),
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->not->toContain('multiFactorAuthentication');
});

it('does not add a second call on re-run', function () {
    $content = <<<'PHP'
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->profile(EditProfile::class)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ], isRequired: app()->isProduction());
    }
}
PHP;

    $result = createPhpParserHelper()->testParseContent($content, [
        new AddMultiFactorAuthentication([MfaMethod::App]),
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect(substr_count($result, 'multiFactorAuthentication('))
        ->toBe(1);
});

it('renders the call multiline after profile', function () {
    $result = createPhpParserHelper()->testParseContent(panelProviderStub(), [
        new AddMultiFactorAuthentication([MfaMethod::App]),
    ], 'app/Providers/Filament/AdminPanelProvider.php');

    expect($result)
        ->toMatch('/->profile\(EditProfile::class\)\s*\n\s*->multiFactorAuthentication\(\[\s*\n/s');
});
