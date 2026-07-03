<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddCanAccessPanel;
use Gizburdt\Cook\Commands\NodeVisitors\AddMfaAuthenticationMethods;
use Gizburdt\Cook\Enums\MfaMethod;

function userModelStub(): string
{
    return <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //
}
PHP;
}

it('adds app contracts, traits and use statements', function () {
    $result = createPhpParserHelper()->testParseContent(userModelStub(), [
        new AddMfaAuthenticationMethods([MfaMethod::App]),
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('implements HasAppAuthentication, HasAppAuthenticationRecovery')
        ->toContain('use InteractsWithAppAuthentication;')
        ->toContain('use InteractsWithAppAuthenticationRecovery;')
        ->toContain('use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;');
});

it('adds email contracts, traits and use statements', function () {
    $result = createPhpParserHelper()->testParseContent(userModelStub(), [
        new AddMfaAuthenticationMethods([MfaMethod::Email]),
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('implements HasEmailAuthentication')
        ->toContain('use InteractsWithEmailAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;')
        ->toContain('use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;')
        ->not->toContain('HasAppAuthentication');
});

it('adds both app and email when both selected', function () {
    $result = createPhpParserHelper()->testParseContent(userModelStub(), [
        new AddMfaAuthenticationMethods([MfaMethod::App, MfaMethod::Email]),
    ], 'app/Models/User.php');

    expect($result)
        ->toContain('HasAppAuthentication')
        ->toContain('HasAppAuthenticationRecovery')
        ->toContain('HasEmailAuthentication')
        ->toContain('use InteractsWithAppAuthentication;')
        ->toContain('use InteractsWithEmailAuthentication;');
});

it('adds nothing when no methods selected', function () {
    $result = createPhpParserHelper()->testParseContent(userModelStub(), [
        new AddMfaAuthenticationMethods([]),
    ], 'app/Models/User.php');

    expect($result)
        ->not->toContain('Authentication')
        ->not->toContain('implements');
});

it('does not duplicate contracts, traits or use statements on re-run', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAppAuthentication, HasAppAuthenticationRecovery
{
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
}
PHP;

    $result = createPhpParserHelper()->testParseContent($content, [
        new AddMfaAuthenticationMethods([MfaMethod::App]),
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use InteractsWithAppAuthentication;'))
        ->toBe(1)
        ->and(substr_count($result, 'use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;'))
        ->toBe(1)
        ->and(substr_count($result, 'implements HasAppAuthentication,'))
        ->toBe(1);
});

it('works together with AddCanAccessPanel without duplicates', function () {
    $result = createPhpParserHelper()->testParseContent(userModelStub(), [
        new AddCanAccessPanel,
        new AddMfaAuthenticationMethods([MfaMethod::App]),
    ], 'app/Models/User.php');

    expect(substr_count($result, 'use Filament\Panel;'))
        ->toBe(1)
        ->and(substr_count($result, 'public function canAccessPanel'))
        ->toBe(1);
});
